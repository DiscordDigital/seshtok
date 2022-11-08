# seshtok
A very simple token bucket algorithm written in PHP.

## Example use
```php
// Include class in your project.
include 'seshtok.php';

// Initiate token bucket.
$seshtok = new seshtok();

// Configure token bucket.
$seshtok->setMinuteRate(5)
        ->setMaxHits(256)
        ->setFreeTokens(20);

// Block consumer if needed, or until 20 free tokens are used up.
$seshtok->consume(4);

// Do something here
```
