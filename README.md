# PhpMimeParser
Php mime message parser

### How to
```php
// Load .eml mime message from file
$str = file_get_contents('mime-mixed-related-alternative.eml');

// Format output
echo "<pre>";

// Create object MimeParser
$m = new PhpMimeParser($str);

// Show Emails
print_r($m->mTo);
print_r($m->mFrom);
print_r($m->mBcc);
print_r($m->mCc);

// Show Message
echo $m->mSubject;
echo $m->mHtml;
echo $m->mText;
print_r($m->mInlineList);

// Show Files
print_r($m->mFiles);

```
