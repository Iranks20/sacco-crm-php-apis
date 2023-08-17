# FlashMessage

A simple PHP container for passing session (flash) messages between page redirects.

## Installation

Install the FlashMessage package via Composer:

```
composer require thecodemill/flash-message
```

## Usage

FlashMessage is used to make messages of 4 different statuses:

- INFO
- SUCCESS
- WARNING
- DANGER

A message can be created by instantiating a new `TheCodeMill\FlashMessage\Message` class, with the appropriate arguments:

```php
use TheCodeMill\FlashMessage\Message;

$message = new Message('The problem with the gene pool is there’s no lifeguard', Message::STATUS_INFO);
```

Alternatively, static helper methods are available to automatically generate a message of each particular status:

```php
use TheCodeMill\FlashMessage\Message;

// Info
$info = Message::info('I have noticed something strange.');

// Warning
$warning = Message::warning('This could be a problem.');

// Danger
$danger = Message::danger('Yes, it was definitely a problem.');

// Success
$success = Message::success('All fixed!');

```

These messages can then be passed into session storage and flashed between page redirects.

Here's a Laravel example:

```php
Route::post('/submit', function () {
    // Do something
    // ...
    
    // Now redirect
    return redirect('/success')
        ->with('message', Message::success('Thanks for submitting.'));
});
```

To output the message, we may use the `content()` and `status()` methods on the Message object.

**Note:** You may notice that the status types tie in nicely with Bootstrap's default types.

Here's another Laravel Blade example:

```php
@if($message = session('message'))
    <div class="alert alert-{{ strtolower($message->status()) }} alert-dismissible" role="alert">
    	<button type="button" class="close" data-dismiss="alert">
    		<span>&times;</span>
    		<span class="sr-only">Close</span>
    	</button>
    
        {{ $message->content() }}
    </div>
@endif
```

## Author

* [Andrew Robinson](https://twitter.com/ap_robinson)
