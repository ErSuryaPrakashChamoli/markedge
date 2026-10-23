<?php

namespace App\Policies;

class NewsletterSubscriberPolicy extends PermissionPolicy
{
    protected string $subject = 'newsletter_subscribers';
}
