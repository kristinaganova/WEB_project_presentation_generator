const express = require("express");
const puppeteer = require("puppeteer");
const cors = require("cors");
const amqplib = require("amqplib");

const app = express();
const PORT = process.env.PORT || 3001;
const PDF_RPC_QUEUE = process.env.PDF_RPC_QUEUE || "pdf.generate";
const RABBITMQ_URL =
  process.env.RABBITMQ_URL || "amqp://guest:guest@127.0.0.1:5672/";

// Middleware
app.use(cors());
app.use(express.json({ limit: "50mb" }));
app.use(express.urlencoded({ extended: true, limit: "50mb" }));

// Request logging
app.use((req, res, next) => {
  console.log(`[${new Date().toISOString()}] ${req.method} ${req.path}`);
  next();
});

// Health check endpoint
app.get("/health", (req, res) => {
  res.json({
    status: "OK",
    service: "PDF Generator Microservice",
    version: "1.0.0",
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    rabbitmq: {
      url: RABBITMQ_URL.replace(/\/\/.*@/, "//***:***@"),
      queue: PDF_RPC_QUEUE,
    },
  });
});

async function generatePdfBase64({ html, title, options }) {
  const startTime = Date.now();

  if (!html) {
    const err = new Error("HTML content is required");
    err.code = "VALIDATION_ERROR";
    throw err;
  }

  // Launch headless browser
  const browser = await puppeteer.launch({
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

  try {
    const page = await browser.newPage();

    // Set viewport for presentation size
    await page.setViewport({
      width: options?.width || 1920,
      height: options?.height || 1080,
      deviceScaleFactor: 1,
    });

    // Set HTML content
    await page.setContent(html, {
      // With embedded assets, we don't need networkidle0 (avoids hangs on external resources).
      waitUntil: "domcontentloaded",
      timeout: 60000,
    });

    // Generate PDF
    const pdfOptions = {
      format: options?.format || "A4",
      landscape: options?.landscape !== undefined ? options.landscape : true,
      printBackground: true,
      preferCSSPageSize: false,
      margin: {
        top: options?.margin?.top || "20px",
        right: options?.margin?.right || "20px",
        bottom: options?.margin?.bottom || "20px",
        left: options?.margin?.left || "20px",
      },
    };

    const pdf = await page.pdf(pdfOptions);
    const duration = Date.now() - startTime;

    return {
      success: true,
      pdf: Buffer.from(pdf).toString("base64"),
      filename: `${title || "presentation"}.pdf`,
      metadata: {
        size: Math.round(pdf.length / 1024),
        sizeUnit: "KB",
        pages: "auto",
        generationTime: duration,
        generationTimeUnit: "ms",
      },
    };
  } finally {
    await browser.close();
  }
}

async function startRabbitMqWorker() {
  console.log(
    `[RabbitMQ] Connecting: ${RABBITMQ_URL.replace(/\/\/.*@/, "//***:***@")}`
  );

  const conn = await amqplib.connect(RABBITMQ_URL);
  const channel = await conn.createChannel();

  await channel.assertQueue(PDF_RPC_QUEUE, { durable: true });
  await channel.prefetch(1);

  console.log(`[RabbitMQ] Worker listening on queue: ${PDF_RPC_QUEUE}`);

  channel.consume(PDF_RPC_QUEUE, async (msg) => {
    if (!msg) return;

    const correlationId = msg.properties.correlationId;
    const replyTo = msg.properties.replyTo;

    let reply = null;

    try {
      const body = msg.content.toString("utf-8");
      const data = JSON.parse(body);

      console.log(
        `[RabbitMQ] Job received (corrId=${correlationId || "n/a"}) title="${
          data?.title || "untitled"
        }" htmlSize=${data?.html?.length || 0}`
      );

      reply = await generatePdfBase64({
        html: data.html,
        title: data.title,
        options: data.options,
      });
    } catch (err) {
      console.error("[RabbitMQ] Job error:", err?.message || err);
      reply = {
        success: false,
        error: "PDF generation failed",
        message: err?.message || String(err),
      };
    }

    try {
      if (replyTo) {
        channel.sendToQueue(replyTo, Buffer.from(JSON.stringify(reply)), {
          correlationId,
          contentType: "application/json",
        });
      } else {
        console.warn("[RabbitMQ] No replyTo set; dropping reply");
      }
    } finally {
      channel.ack(msg);
    }
  });

  conn.on("error", (e) => console.error("[RabbitMQ] connection error", e));
  conn.on("close", () => console.error("[RabbitMQ] connection closed"));
}

// Generate PDF endpoint
app.post("/generate-pdf", async (req, res) => {
  console.log("[PDF Generation] Request received");

  try {
    const { html, title, options } = req.body;

    console.log(`[PDF Generation] Processing: ${title || "untitled"}`);
    console.log(`[PDF Generation] HTML size: ${html?.length || 0} characters`);

    const result = await generatePdfBase64({ html, title, options });
    res.json(result);
  } catch (error) {
    console.error(`[PDF Generation] Error:`, error.message);
    console.error("[PDF Generation] Stack:", error.stack);

    res.status(500).json({
      success: false,
      error: "PDF generation failed",
      message: error.message,
      details: process.env.NODE_ENV === "development" ? error.stack : undefined,
    });
  }
});

// Batch generation endpoint (bonus feature)
app.post("/generate-pdf-batch", async (req, res) => {
  try {
    const { presentations } = req.body;

    if (!Array.isArray(presentations) || presentations.length === 0) {
      return res.status(400).json({
        success: false,
        error: "Array of presentations required",
      });
    }

    console.log(`[Batch PDF] Processing ${presentations.length} presentations`);

    const results = [];

    for (let i = 0; i < presentations.length; i++) {
      const { html, title } = presentations[i];
      console.log(
        `[Batch PDF] Processing ${i + 1}/${presentations.length}: ${title}`
      );

      try {
        const browser = await puppeteer.launch({
          headless: "new",
          args: ["--no-sandbox", "--disable-setuid-sandbox"],
        });

        const page = await browser.newPage();
        await page.setContent(html, { waitUntil: "networkidle0" });
        const pdf = await page.pdf({ format: "A4", landscape: true });
        await browser.close();

        results.push({
          success: true,
          title: title,
          pdf: pdf.toString("base64"),
        });
      } catch (error) {
        results.push({
          success: false,
          title: title,
          error: error.message,
        });
      }
    }

    const successCount = results.filter((r) => r.success).length;
    console.log(
      `[Batch PDF] Completed: ${successCount}/${presentations.length} successful`
    );

    res.json({
      success: true,
      total: presentations.length,
      successful: successCount,
      results: results,
    });
  } catch (error) {
    console.error("[Batch PDF] Error:", error.message);
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
});

// Service info endpoint
app.get("/", (req, res) => {
  res.json({
    service: "PDF Generator Microservice",
    version: "1.0.0",
    endpoints: {
      health: "GET /health",
      generate: "POST /generate-pdf",
      batch: "POST /generate-pdf-batch",
    },
    documentation: "See README.md for usage examples",
    status: "running",
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    error: "Endpoint not found",
    path: req.path,
  });
});

// Error handler
app.use((err, req, res, next) => {
  console.error("[Server Error]", err);
  res.status(500).json({
    success: false,
    error: "Internal server error",
    message: err.message,
  });
});

// Start server
app.listen(PORT, () => {
  console.log("═══════════════════════════════════════════════════════");
  console.log("   PDF Generator Microservice");
  console.log("═══════════════════════════════════════════════════════");
  console.log(`   Status: RUNNING`);
  console.log(`   Port: ${PORT}`);
  console.log(`   URL: http://localhost:${PORT}`);
  console.log(`   Health Check: http://localhost:${PORT}/health`);
  console.log("═══════════════════════════════════════════════════════");
  console.log("   Endpoints:");
  console.log(`   POST /generate-pdf - Generate single PDF`);
  console.log(`   POST /generate-pdf-batch - Generate multiple PDFs`);
  console.log(`   GET  /health - Service health check`);
  console.log("═══════════════════════════════════════════════════════");
  console.log(`   RabbitMQ RPC queue: ${PDF_RPC_QUEUE}`);
  console.log("═══════════════════════════════════════════════════════");
});

// Start RabbitMQ worker (non-fatal if RabbitMQ is down, but PDF via queue won't work)
startRabbitMqWorker().catch((err) => {
  console.error("[RabbitMQ] Failed to start worker:", err?.message || err);
});

// Graceful shutdown
process.on("SIGTERM", () => {
  console.log("[Server] SIGTERM received, shutting down gracefully...");
  process.exit(0);
});

process.on("SIGINT", () => {
  console.log("\n[Server] SIGINT received, shutting down gracefully...");
  process.exit(0);
});
