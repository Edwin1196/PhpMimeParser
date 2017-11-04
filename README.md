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

// Save attachments to folder
foreach ($m->mFiles as $key => $file) {	
	$dir = 'attachments';
	if (!file_exists($dir)) {
	  mkdir($dir);
	}	
	//  Save content to file
	file_put_contents($dir.'/'.basename($file['name']), $file['content']);
}

// Get header line with Date:
echo $m->getFromHeader('Content-Type');
echo $m->getFromHeader('Bcc');

```
