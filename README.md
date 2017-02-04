Tactician Domain Events Symfony Bundle
======================================

Symfony Bundle to integrate [Tactician Domain Events library](https://bornfreee.github.io/tactician-domain-events/) with Symfony project

Installation
------------

Install via composer

```
composer require bornfreee/tactician-domain-events-bundle
```

Usage
-----

This bundle allows you to automatically have Domain Events dispatched by `EventDispatcher`. It also allows to register event listeners as the Symfony Services.
You can register as many listeners as you want for each Domain Event.

First, we need to install the Tactician official Bundle to integrate the command bus library:

```php
composer require league/tactician-bundle
```

Then we need to configure Middleware to automatically record the Domain Events and dispatch them.
We only want to handle the events themselves *after* the command has completely and successfully been handled. So we add the middleware that records the Domain Events *before* the Transaction middleware.

It means that as soon as transaction is completed, the Domain Events will be recorded:

```php
tactician:
    commandbus:
        default:
            middleware:
                # other middlewares...
                - tactician_domain_events.middleware.release_recorded_events # make sure to add it before `tactician.middleware.doctrine` 
                - tactician.middleware.doctrine
                - tactician.middleware.command_handler
```

### Configuring Event Listeners

In order to add event listeners for dispatched Domain Events, we need to define services and the corresponded commands for them:

```php
app.listener.send_email:
    class: AppBundle\EventListener\SendEmailAfterUserIsCreatedListener
    tags:
        - { name: tactician.event_listener, event: AppBundle\Controller\UserWasCreated }
```

Notice the tag `tactician.event_listener`. The bundle automatically finds all services tagged with this tag and adds the listener to `EventDispatcher`.

This is all configuration you need to start using the Tactician command bus with Domain Events.

Let's have an example where we create a new user and a `UserWasCreated` domain event is dispatched:

```php
class User implements ContainsRecordedEvents
{
    use EventRecorderCapabilities;

    public function __construct($name)
    {
        $this->name = $name;

        $this->record(new UserWasCreated($name));
    }

    // ...
}
```

As soon as this `Entity` is successfully created, the `SendEmailAfterUserIsCreatedListener` will be dispatched.

License
-------

Copyright (c) 2017, Maks Rafalko

Under MIT license, read LICENSE file.
