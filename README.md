# OneloginSamlBundle
OneLogin SAML Bundle for Symfony. (https://github.com/onelogin/php-saml)

[![Latest Stable Version](https://poser.pugx.org/hslavich/oneloginsaml-bundle/v)](//packagist.org/packages/hslavich/oneloginsaml-bundle) [![Latest Unstable Version](https://poser.pugx.org/hslavich/oneloginsaml-bundle/v/unstable)](//packagist.org/packages/hslavich/oneloginsaml-bundle) [![Total Downloads](https://poser.pugx.org/hslavich/oneloginsaml-bundle/downloads)](//packagist.org/packages/hslavich/oneloginsaml-bundle)  [![License](https://poser.pugx.org/hslavich/oneloginsaml-bundle/license)](//packagist.org/packages/hslavich/oneloginsaml-bundle)

[![Build Status](https://travis-ci.org/hslavich/OneloginSamlBundle.svg?branch=master)](https://travis-ci.org/hslavich/OneloginSamlBundle)
[![Coverage Status](https://coveralls.io/repos/github/hslavich/OneloginSamlBundle/badge.svg?branch=master)](https://coveralls.io/github/hslavich/OneloginSamlBundle?branch=master)

[![SensioLabsInsight](https://insight.symfony.com/projects/d74ae361-ef8d-437e-b8d6-a8627491ccfa/big.png)](https://insight.symfony.com/projects/d74ae361-ef8d-437e-b8d6-a8627491ccfa)

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/hslavich)

Installation
------------

Install with composer
``` json
"require": {
    "hslavich/oneloginsaml-bundle": "^2.0"
}
```

> Using of `dev-master` version deprecated, use a specific version instead (i.e. 2.0).<br>
> In the future `master` branch will be removed (approximately in the fall '21).

Run composer update
``` bash
composer update hslavich/oneloginsaml-bundle
```

Enable the bundle in `config/bundles.php`
``` php
return [
    // ...
    Hslavich\OneloginSamlBundle\HslavichOneloginSamlBundle::class => ['all' => true],
]
```

Configuration
-------------

Configure SAML metadata in `config/packages/hslavich_onelogin_saml.yaml`. Check https://github.com/onelogin/php-saml#settings for more info.
``` yml
hslavich_onelogin_saml:
    # Basic settings
    idp:
        entityId: 'http://id.example.com/saml2/idp/metadata.php'
        singleSignOnService:
            url: 'http://id.example.com/saml2/idp/SSOService.php'
            binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
        singleLogoutService:
            url: 'http://id.example.com/saml2/idp/SingleLogoutService.php'
            binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
        x509cert: ''
    sp:
        entityId: 'http://myapp.com/app_dev.php/saml/metadata'
        assertionConsumerService:
            url: 'http://myapp.com/app_dev.php/saml/acs'
            binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
        singleLogoutService:
            url: 'http://myapp.com/app_dev.php/saml/logout'
            binding: 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
        privateKey: ''    
    # Optional settings
    baseurl: 'http://myapp.com'
    strict: true
    debug: true    
    security:
        nameIdEncrypted: false
        authnRequestsSigned: false
        logoutRequestSigned: false
        logoutResponseSigned: false
        wantMessagesSigned: false
        wantAssertionsSigned: false
        wantNameIdEncrypted: false
        requestedAuthnContext: true
        signMetadata: false
        wantXMLValidation: true
        relaxDestinationValidation: false
        destinationStrictlyMatches: true
        rejectUnsolicitedResponsesWithInResponseTo: false
        signatureAlgorithm: 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
        digestAlgorithm: 'http://www.w3.org/2001/04/xmlenc#sha256'
    contactPerson:
        technical:
            givenName: 'Tech User'
            emailAddress: 'techuser@example.com'
        support:
            givenName: 'Support User'
            emailAddress: 'supportuser@example.com'
        administrative:
            givenName: 'Administrative User'
            emailAddress: 'administrativeuser@example.com'
    organization:
        en:
            name: 'Example'
            displayname: 'Example'
            url: 'http://example.com'
```

If you don't want to set contactPerson or organization, don't add those parameters instead of leaving them blank.

Configure firewall and user provider in `config/packages/security.yaml`
``` yml
security:
    # ...

    providers:
        saml_provider:
            # Basic provider instantiates a user with default roles
            saml:
                user_class: 'AppBundle\Entity\User'
                default_roles: ['ROLE_USER']

    firewalls:
        app:
            pattern: ^/
            saml:
                # Match SAML attribute 'uid' with username.
                # Uses getNameId() method by default.
                username_attribute: uid
                # Use the attribute's friendlyName instead of the name 
                use_attribute_friendly_name: true
                check_path: saml_acs
                login_path: saml_login
            logout:
                path: saml_logout

    access_control:
        - { path: ^/saml/login, roles: PUBLIC_ACCESS }
        - { path: ^/saml/metadata, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: ROLE_USER }
```

Edit your `config/routing` or `config/routes.yaml` depending on your Symfony version.
``` yml
hslavich_saml_sp:
    resource: "@HslavichOneloginSamlBundle/Resources/config/routing.yml"
```

Inject SAML attributes into User object (Optional)
--------------------------------------------------
Your user class must implement `SamlUserInterface`

``` php
<?php

namespace App\Entity;

use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;

class User implements SamlUserInterface
{
    protected $username;
    protected $email;

    // ...

    public function setSamlAttributes(array $attributes)
    {
        $this->email = $attributes['mail'][0];
    }
}
```

Then you can get attributes from user object
``` php
$email = $this->getUser()->getEmail();
```

Integration with classic login form
-----------------------------------

You can integrate SAML authentication with traditional login form by editing your `security.yaml`:

``` yml
security:
    enable_authenticator_manager: true

    providers:
        user_provider:
            # Loads user from user repository
            entity:
                class: App:User
                property: username

    firewalls:
        default:
            saml:
                username_attribute: uid
                check_path: saml_acs
                login_path: saml_login
                failure_path: saml_login
                always_use_default_target_path: true

            # Traditional login form
            form_login:
                login_path: /login
                check_path: /login_check
                always_use_default_target_path: true

            logout:
                path: saml_logout
```

Then you can add a link to route `saml_login` in your login page in order to start SAML sign on.

``` html
    <a href="{{ path('saml_login') }}">SAML Login</a>
```

Just-in-time user provisioning (optional)
-----------------------------------------

It's possible to have a new user provisioned based off the received SAML attributes when the user provider cannot find a
user.

Edit firewall settings in `security.yaml`:

``` yml
security:
    # ...

    providers:
        saml_provider:
            # Loads user from user repository
            entity:
                class: App\Entity\User
                property: username

    firewalls:
        enable_authenticator_manager: true
    
        default:
            provider: saml_provider
            saml:
                username_attribute: uid
                # User factory service
                user_factory: my_user_factory
            logout:
            path: saml_logout
```

Create the user factory service editing `services.yaml`:

``` yml
services:
    my_user_factory:
        class: Hslavich\OneloginSamlBundle\Security\User\SamlUserFactory
        arguments:
            # User class
            - App\Entity\User
            # Attribute mapping.
            - password: 'notused'
              email: $mail
              name: $cn
              lastname: $sn
              roles: ['ROLE_USER']
```

Fields with '$' references to SAML attribute value.

Or you can create your own User Factory that implements `SamlUserFactoryInterface`

``` php
<?php

namespace App\Security;

use App\Entity\User;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserFactory implements SamlUserFactoryInterface
{
    public function createUser($username, array $attributes = []): UserInterface
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $user->setUsername($username);
        $user->setPassword('notused');
        $user->setEmail($attributes['mail'][0]);
        $user->setName($attributes['cn'][0]);
    
        return $user;
    }
}
```

``` yml
services:
    my_user_factory:
        class: App\Security\UserFactory
```

> For versions prior to 2.1 the `createUser` signature was different:
> ```php
> public function createUser(SamlTokenInterface $token): UserInterface
> {
>     $username = $token->getUsername();
>     $attributes = $token->getAttributes();
>     ...
> }
> ```

Persist user on creation and SAML attributes injection (Optional)
-----------------------------------------------------------------

> Symfony EventDispatcher component and Doctrine ORM are required.

Edit firewall settings in `security.yaml`:

``` yml
security:
    # ...

    firewalls:
        # ...

        default:
            saml:
                # ...
                persist_user: true
```

> In order for the user to be persisted, you must use a user provider that throws `UserNotFoundException` (e.g.
> `EntityUserProvider` as used in the example above). The `SamlUserProvider` does not throw this exception which will
> cause an empty user to be returned when a matching user cannot be found.

To use non-default entity manager specify it name by `hslavich_onelogin_saml.entityManagerName` config option.

User persistence is performing by event listeners `Hslavich\OneloginSamlBundle\EventListener\User\UserCreatedListener`
and `Hslavich\OneloginSamlBundle\EventListener\User\UserModifiedListener` that can be decorated if necessary to override
the default behavior. Also, you can make your own listeners for `Hslavich\OneloginSamlBundle\Event\UserCreatedEvent`
and `Hslavich\OneloginSamlBundle\Event\UserModifiedEvent` events.
