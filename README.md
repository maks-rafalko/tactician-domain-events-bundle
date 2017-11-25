Tactician Domain Events Symfony Bundle
======================================

[![Build Status](https://travis-ci.org/borNfreee/tactician-domain-events-bundle.svg?branch=master)](https://travis-ci.org/borNfreee/tactician-domain-events-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/borNfreee/tactician-domain-events-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/borNfreee/tactician-domain-events-bundle/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/bornfreee/tactician-domain-events-bundle/v/stable)](https://packagist.org/packages/bornfreee/tactician-domain-events-bundle)

Symfony Bundle to integrate [Tactician Domain Events library](https://bornfreee.github.io/tactician-domain-events/) with Symfony project

Installation
------------

Install via composer

```bash
composer require bornfreee/tactician-domain-events-bundle
```

Configuration
-----

On default event collector `CollectsEventsFromEntities` will be used, but sometimes you might record an event when entity doesn't change so the event is not collected.
It's also true when an aggregate root is recording events for its child entities. To collect those events you need to use `CollectsEventsFromAllEntitiesManagedByUnitOfWork`
That one collects events from all entities which are managed by Unit of Work. To use it you need to set `collect_from_all_managed_entities` on true:

```yaml
tactician_domain_event:
    collect_from_all_managed_entities: true # it's false on default
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

```yaml
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

```yaml
app.listener.send_email:
    class: AppBundle\EventListener\SendEmailAfterUserIsCreatedListener
    tags:
        - { name: tactician.event_listener, event: App\Domain\Events\UserWasCreated }
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

As soon as this `Entity` is successfully created, the `SendEmailAfterUserIsCreatedListener` will be triggered.

### Debugging

You can run the `debug:tactician-domain-events` command to get a list of all events with mapped listeners.

License
-------

Copyright (c) 2017, Maks Rafalko

Under MIT license, read LICENSE file.
