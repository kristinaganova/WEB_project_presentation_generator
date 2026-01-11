const express = require("express");
const puppeteer = require("puppeteer");
const cors = require("cors");

const app = express();
const PORT = process.env.PORT || 3001;

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
  });
});

// Generate PDF endpoint
app.post("/generate-pdf", async (req, res) => {
  const startTime = Date.now();
  console.log("[PDF Generation] Request received");

  try {
    const { html, title, options } = req.body;

    // Validation
    if (!html) {
      console.error("[PDF Generation] Missing HTML content");
      return res.status(400).json({
        success: false,
        error: "HTML content is required",
      });
    }

    console.log(`[PDF Generation] Processing: ${title || "untitled"}`);
    console.log(`[PDF Generation] HTML size: ${html.length} characters`);

    // Launch headless browser
    console.log("[PDF Generation] Launching browser...");
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

    const page = await browser.newPage();
    console.log("[PDF Generation] Browser launched");

    // Set viewport for presentation size
    await page.setViewport({
      width: options?.width || 1920,
      height: options?.height || 1080,
      deviceScaleFactor: 1,
    });

    // Set HTML content
    console.log("[PDF Generation] Setting HTML content...");
    await page.setContent(html, {
      waitUntil: "networkidle0",
      timeout: 30000,
    });

    // Generate PDF
    console.log("[PDF Generation] Generating PDF...");
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

    await browser.close();
    console.log("[PDF Generation] Browser closed");

    // Convert Buffer to base64 (PDF is already a Buffer from Puppeteer)
    const pdfBase64 = Buffer.from(pdf).toString("base64");
    const pdfSize = pdf.length / 1024; // Size in KB
    const duration = Date.now() - startTime;

    console.log(
      `[PDF Generation] Success! Size: ${pdfSize.toFixed(
        2
      )} KB, Duration: ${duration}ms`
    );

    res.json({
      success: true,
      pdf: pdfBase64,
      filename: `${title || "presentation"}.pdf`,
      metadata: {
        size: Math.round(pdfSize),
        sizeUnit: "KB",
        pages: "auto",
        generationTime: duration,
        generationTimeUnit: "ms",
      },
    });
  } catch (error) {
    const duration = Date.now() - startTime;
    console.error(`[PDF Generation] Error after ${duration}ms:`, error.message);
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
