const puppeteer = require("puppeteer");
const fs = require("fs");

async function testPDF() {
  console.log("Starting PDF test...");

  try {
    const browser = await puppeteer.launch({
      headless: "new",
      args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
      ],
      executablePath: puppeteer.executablePath(),
    });

    console.log("Browser launched");

    const page = await browser.newPage();

    await page.setContent("<html><body><h1>Test PDF</h1></body></html>");

    console.log("Generating PDF...");
    const pdf = await page.pdf({
      format: "A4",
      printBackground: true,
    });

    await browser.close();

    // Check if PDF is valid
    console.log("PDF generated. Size:", pdf.length, "bytes");
    console.log("First 4 bytes:", pdf.slice(0, 4).toString());
    console.log("First 10 bytes (hex):", pdf.slice(0, 10).toString("hex"));

    // Save to file for testing
    fs.writeFileSync("test-output.pdf", pdf);
    console.log("PDF saved to test-output.pdf");

    // Test base64
    const base64 = pdf.toString("base64");
    const decoded = Buffer.from(base64, "base64");
    console.log("Base64 length:", base64.length);
    console.log("Decoded first 4 bytes:", decoded.slice(0, 4).toString());

    if (decoded.slice(0, 4).toString() === "%PDF") {
      console.log("✓ PDF is valid!");
    } else {
      console.log("✗ PDF is INVALID!");
    }
  } catch (error) {
    console.error("Error:", error.message);
  }
}

testPDF();
