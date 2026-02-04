# FAQ Transfer Command

Parse FAQ data from Word-exported HTML files and output as structured arrays (JSON or text format).

## Overview

This command extracts FAQ data from HTML files exported by Microsoft Word and outputs the parsed content as:
- **JSON format** - Machine-readable structured data
- **Text format** - Human-readable formatted text

The command automatically handles image extraction and path rewriting, making images available in the public directory.

## Usage

### Basic Usage

Parse HTML and print to console (default JSON format):
```bash
php bin/console app:faq-transfer /path/to/FAQ.html en
```

### Save to JSON File

```bash
php bin/console app:faq-transfer /path/to/FAQ.html en --output-file=faq_data.json
```

### Save as Text Format

```bash
php bin/console app:faq-transfer /path/to/FAQ.html en --format=text --output-file=faq_data.txt
```

### With Custom Image Source Path

If the `.fld` folder is in a different location:
```bash
php bin/console app:faq-transfer /path/to/FAQ.html en --source-path=/custom/path
```

## Arguments

| Argument | Required | Description |
|----------|----------|-------------|
| `html-file` | Yes | Full path to the HTML file exported from Word |
| `locale` | No | Locale code: `en` (default) or `ch` |

## Options

| Option | Short | Description |
|--------|-------|-------------|
| `--source-path` | `-s` | Source directory containing the `.fld` folder (default: HTML file directory) |
| `--output-file` | `-o` | Save output to file instead of console |
| `--format` | `-f` | Output format: `json` (default) or `text` |

## Expected HTML Structure

The command expects FAQ data formatted as follows in Word-exported HTML:

### Category
- Must be underlined text
- Must have font size 16pt
- Example: `<u><span style="font-size:16.0pt">Category Name</span></u>`

### Question
- Must be in a paragraph (`<p>`)
- Must start with bold numbered text
- Example: `<p><b>1. What is this?</b></p>`

### Answer
- Any HTML content following the question
- Stops at the next category or question
- Can contain images, formatting, etc.

Example structure:
```html
<u><span style="font-size:16.0pt">General Questions</span></u>

<p><b>1. What is this product?</b></p>
<p>This is our amazing product that does wonderful things.</p>

<p><b>2. How do I use it?</b></p>
<p>Simply follow these steps:</p>
<ul><li>Step 1</li><li>Step 2</li></ul>

<u><span style="font-size:16.0pt">Billing</span></u>

<p><b>1. What is the price?</b></p>
<p>The price is $99.99 per year.</p>
```

## Image Handling

Images are automatically extracted from the `.fld` folder (created by Word's HTML export process):

1. **Source**: `.fld` folder in the same directory as the HTML file (or custom `--source-path`)
2. **Destination**: `public/build/images/FAQ/`
3. **Path Rewriting**: Image paths in HTML are rewritten from Word format to `/build/images/FAQ/imagename.png`

### Example

If you export `FAQ.html`, Word creates:
- `FAQ.html` - Main HTML file
- `FAQ.fld/` - Folder containing images

The command:
1. Copies all images from `FAQ.fld/` to `public/build/images/FAQ/`
2. Rewrites image paths in the parsed data to use `/build/images/FAQ/imagename.png`

## Output Format

### JSON Output

```json
{
  "locale": "en",
  "total_count": 2,
  "faqs": [
    {
      "category": "General Questions",
      "question": "1. What is this product?",
      "answer": "<p>This is our amazing product...</p>"
    },
    {
      "category": "Billing",
      "question": "1. What is the price?",
      "answer": "<p>The price is $99.99 per year.</p>"
    }
  ]
}
```

### Text Output

```
LOCALE: en
TOTAL COUNT: 2
================================================================================

FAQ #1
CATEGORY: General Questions
QUESTION: 1. What is this product?
ANSWER:
This is our amazing product that does wonderful things.
--------------------------------------------------------------------------------

FAQ #2
CATEGORY: Billing
QUESTION: 1. What is the price?
ANSWER:
The price is $99.99 per year.
```

## Typical Workflow

1. Export FAQ document from Word as HTML
2. Run the command to parse and save to JSON
3. Copy the JSON output
4. Paste into your application's FAQ management system

Example complete command:
```bash
php bin/console app:faq-transfer /Users/me/Downloads/FAQ.html en --output-file=faq_parsed.json
```

This creates `faq_parsed.json` containing all parsed FAQ data with properly rewritten image paths.

## Console Output

The command provides real-time feedback:

```
 FAQ Parser - Locale: en
 Source HTML: /path/to/FAQ.html
 Processing images from .fld folder...
 Copied 5 image(s) to /path/to/public/build/images/FAQ/
 Rewritten 5 image path(s)
 Parsing FAQ data from HTML...
 [OK] Found 12 FAQ entries                                                    
 Preview - First 3 FAQs

 FAQ #1 - General Questions
 Q: 1. What is this product?
 A: This is our amazing product...

 FAQ #2 - General Questions
 Q: 2. How do I use it?
 A: Simply follow these steps...

 FAQ #3 - General Questions
 Q: 3. Where can I buy?
 A: You can purchase from...

 [OK] Output saved to: faq_parsed.json
```

## Troubleshooting

### "FLD folder not found"
- Ensure the `.fld` folder exists in the same directory as the HTML file
- Use `--source-path` if the folder is in a different location

### "No FAQ data found"
- Check that categories use `<u><span style="font-size:16.0pt">` format
- Verify questions start with bold numbered text like `<b>1. Question?</b>`
- Ensure there's no extra spacing or formatting that might interfere with detection

### Images not copying
- Check file permissions on `public/build/images/FAQ/` directory
- Ensure the `.fld` folder is readable

### HTML parsing issues
- Some Word exports use different formatting
- Try re-exporting the document as HTML from Word
- Check that paragraph and formatting styles match expected patterns

## Locales Supported

- `en` - English (default)
- `ch` - Chinese

You can use any locale code; it's stored in the output for reference.

## Notes

- Images are copied to the file system; they're not embedded in the output
- The command doesn't modify the original HTML file
- Multiple runs will overwrite existing images with the same filename
- Output is always UTF-8 encoded (JSON with unescaped unicode)