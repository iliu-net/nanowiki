The $context variable of the NanoWiki class contains
a 'debug' flag.  To turn on add to the URL:

- `?debug`

And to turn off add:

- `?nodebug`

You can then use in your code checks such as:

```php
if ($PicoWiki->context['debug']) {
  echo "DEBUG MODE ON!<br>";
}
```

At the time of this writing, `backend/templates/_footer.html` makes
use of this flag to show debug info.
