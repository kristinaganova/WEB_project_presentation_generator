const amqp = require("amqplib");
const puppeteer = require("puppeteer");

/**
 * PDF Generation Microservice with RabbitMQ
 *
 * Listens for PDF generation requests on RabbitMQ queue
 * and generates PDFs using Puppeteer.
 *
 * Architecture:
 * - Receives messages from 'pdf_generation_requests' queue
 * - Processes HTML to PDF conversion
 * - Sends response back to reply_to queue
 *
 * Advantages:
 * - Asynchronous processing
 * - Horizontal scaling (multiple workers)
 * - Message persistence (survives crashes)
 * - Automatic load balancing
 */

const RABBITMQ_URL = process.env.RABBITMQ_URL || "amqp://localhost";
const REQUEST_QUEUE = "pdf_generation_requests";
const PREFETCH_COUNT = 1; // Process one message at a time

let browserInstance = null;

/**
 * Get or create browser instance (reuse for performance)
 */
async function getBrowser() {
  if (!browserInstance || !browserInstance.isConnected()) {
    console.log("[Browser] Launching new Puppeteer instance...");
    browserInstance = await puppeteer.launch({
      headless: "new",
      args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
        "--disable-accelerated-2d-canvas",
        "--no-first-run",
        "--no-zygote",
        "--disable-gpu",
      ],
      executablePath: puppeteer.executablePath(),
      ignoreDefaultArgs: ["--disable-extensions"],
    });
    console.log("[Browser] Browser launched successfully");
  }
  return browserInstance;
}

/**
 * Generate PDF from HTML
 */
async function generatePdf(html, title = "presentation", options = {}) {
  const browser = await getBrowser();
  const page = await browser.newPage();

  try {
    // Set viewport
    await page.setViewport({
      width: options.width || 1920,
      height: options.height || 1080,
      deviceScaleFactor: 1,
    });

    // Set HTML content
    await page.setContent(html, {
      waitUntil: "networkidle0",
      timeout: 30000,
    });

    // Wait for any dynamic content
    await page.waitForTimeout(500);

    // Generate PDF
    const pdfOptions = {
      format: options.format || "A4",
      landscape: options.landscape !== false,
      printBackground: true,
      preferCSSPageSize: false,
      margin: options.margin || {
        top: "20px",
        right: "20px",
        bottom: "20px",
        left: "20px",
      },
    };

    const pdfBuffer = await page.pdf(pdfOptions);

    return pdfBuffer;
  } finally {
    await page.close();
  }
}

/**
 * Start RabbitMQ consumer
 */
async function startRabbitMQConsumer() {
  let connection;
  let channel;

  try {
    console.log(`[RabbitMQ] Connecting to ${RABBITMQ_URL}...`);
    connection = await amqp.connect(RABBITMQ_URL);
    console.log("[RabbitMQ] Connected successfully");

    // Handle connection errors
    connection.on("error", (err) => {
      console.error("[RabbitMQ] Connection error:", err.message);
    });

    connection.on("close", () => {
      console.warn(
        "[RabbitMQ] Connection closed. Reconnecting in 5 seconds...",
      );
      setTimeout(startRabbitMQConsumer, 5000);
    });

    // Create channel
    channel = await connection.createChannel();
    console.log("[RabbitMQ] Channel created");

    // Assert queue exists (create if not)
    await channel.assertQueue(REQUEST_QUEUE, {
      durable: true, // Queue survives broker restart
    });

    // Set prefetch (process one message at a time)
    await channel.prefetch(PREFETCH_COUNT);

    console.log(
      `[RabbitMQ] Waiting for PDF generation requests in queue: ${REQUEST_QUEUE}`,
    );
    console.log("[RabbitMQ] Press CTRL+C to exit");

    // Start consuming messages
    await channel.consume(REQUEST_QUEUE, async (msg) => {
      if (!msg) return;

      const correlationId = msg.properties.correlationId;
      const replyTo = msg.properties.replyTo;
      const startTime = Date.now();

      console.log(
        `\n[${new Date().toISOString()}] ========================================`,
      );
      console.log(`[Request] Correlation ID: ${correlationId}`);
      console.log(`[Request] Reply To: ${replyTo}`);

      try {
        // Parse request
        const requestData = JSON.parse(msg.content.toString());
        const { html, title, options } = requestData;

        if (!html) {
          throw new Error("HTML content is required");
        }

        console.log(`[Processing] Title: ${title || "untitled"}`);
        console.log(`[Processing] HTML size: ${html.length} characters`);
        console.log(`[Processing] Options:`, JSON.stringify(options || {}));

        // Generate PDF
        console.log("[PDF] Starting generation...");
        const pdfBuffer = await generatePdf(html, title, options);
        const duration = Date.now() - startTime;

        console.log(`[PDF] Generated successfully in ${duration}ms`);
        console.log(`[PDF] Size: ${pdfBuffer.length} bytes`);

        // Prepare response
        const response = {
          success: true,
          pdf: pdfBuffer.toString("base64"),
          metadata: {
            title: title,
            size: pdfBuffer.length,
            duration: duration,
            timestamp: new Date().toISOString(),
          },
        };

        // Send response back
        if (replyTo) {
          channel.sendToQueue(replyTo, Buffer.from(JSON.stringify(response)), {
            correlationId: correlationId,
            contentType: "application/json",
          });
          console.log("[Response] Sent successfully to", replyTo);
        }

        // Acknowledge message
        channel.ack(msg);
        console.log("[Complete] Message acknowledged");
      } catch (error) {
        console.error("[Error] PDF generation failed:", error.message);
        console.error("[Error] Stack:", error.stack);

        // Send error response
        const errorResponse = {
          success: false,
          error: error.message,
          timestamp: new Date().toISOString(),
        };

        if (replyTo) {
          try {
            channel.sendToQueue(
              replyTo,
              Buffer.from(JSON.stringify(errorResponse)),
              {
                correlationId: correlationId,
                contentType: "application/json",
              },
            );
            console.log("[Error Response] Sent to", replyTo);
          } catch (sendError) {
            console.error(
              "[Error] Failed to send error response:",
              sendError.message,
            );
          }
        }

        // Acknowledge message (don't requeue errors)
        channel.ack(msg);
        console.log("[Error] Message acknowledged (not requeued)");
      }

      console.log(`========================================\n`);
    });
  } catch (error) {
    console.error("[RabbitMQ] Fatal error:", error.message);
    console.error("[RabbitMQ] Retrying in 5 seconds...");

    if (channel) {
      try {
        await channel.close();
      } catch (e) {}
    }
    if (connection) {
      try {
        await connection.close();
      } catch (e) {}
    }

    setTimeout(startRabbitMQConsumer, 5000);
  }
}

/**
 * Graceful shutdown
 */
process.on("SIGINT", async () => {
  console.log("\n[Shutdown] Received SIGINT, shutting down gracefully...");

  if (browserInstance) {
    await browserInstance.close();
    console.log("[Shutdown] Browser closed");
  }

  console.log("[Shutdown] Exiting...");
  process.exit(0);
});

process.on("SIGTERM", async () => {
  console.log("\n[Shutdown] Received SIGTERM, shutting down gracefully...");

  if (browserInstance) {
    await browserInstance.close();
    console.log("[Shutdown] Browser closed");
  }

  console.log("[Shutdown] Exiting...");
  process.exit(0);
});

/**
 * Start the service
 */
console.log("========================================");
console.log("  PDF Generation Service (RabbitMQ)");
console.log("========================================");
console.log(`Service: PDF Generator with RabbitMQ`);
console.log(`Version: 2.0.0 (Message Queue)`);
console.log(`Node: ${process.version}`);
console.log(`RabbitMQ URL: ${RABBITMQ_URL}`);
console.log(`Queue: ${REQUEST_QUEUE}`);
console.log("========================================\n");

startRabbitMQConsumer().catch((error) => {
  console.error("[Fatal] Service startup failed:", error);
  process.exit(1);
});
