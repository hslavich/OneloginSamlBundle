# OneloginSamlBundle
OneLogin SAML Bundle for Symfony2. (https://github.com/onelogin/php-saml)

Installation
------------

Install with composer
``` json
"require": {
    "hslavich/oneloginsaml-bundle": "dev-master"
}
```

Run composer update
``` bash
composer update hslavich/oneloginsaml-bundle
```

Enable the bundle in `app/AppKernel.php`
``` php
$bundles = array(
    // ...
    new Hslavich\OneloginSamlBundle\HslavichOneloginSamlBundle(),
)
```

Configuration
-------------

Configure SAML metadata in `app/config/config.yml`. Check https://github.com/onelogin/php-saml#settings for more info.
``` yml
hslavich_onelogin_saml:
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
    security:
        nameIdEncrypted:       false
        authnRequestsSigned:   false
        logoutRequestSigned:   false
        logoutResponseSigned:  false
        wantMessagesSigned:    false
        wantAssertionsSigned:  false
        wantNameIdEncrypted:   false
        requestedAuthnContext: true
    contactPerson:
        technical:
            givenName: 'Tech User'
            emailAddress: 'techuser@example.com'
        support:
            givenName: 'Support User'
            emailAddress: 'supportuser@example.com'
    organization:
        en:
            name: 'Example'
            displayname: 'Example'
            url: 'http://example.com'
```

If you don't want to set contactPerson or organization, don't add those parameters instead of leaving them blank. 

Configure firewall and user provider in `app/config/security.yml`
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
            pattern:    ^/
            anonymous: true
            saml:
                # Match SAML attribute 'uid' with username
                username_attribute: uid
                check_path: /saml/acs
                login_path: /saml/login
            logout:
                path: /saml/logout

    access_control:
        - { path: ^/saml/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/saml/metadata, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/, roles: ROLE_USER }
```

Edit your `app/config/routing`
``` yml
hslavich_saml_sp:
    resource: "@HslavichOneloginSamlBundle/Resources/config/routing.yml"
```

Inject SAML attributes into User object (Optional)
--------------------------------------------------
Your user class must implement `SamlUserInterface`

``` php
<?php

namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;

class User implements UserInterface, SamlUserInterface
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

You can integrate SAML authentication with traditional login form by editing your `security.yml`:

``` yml
providers:
    user_provider:
        # Loads user from user repository
        entity:
            class: AppBundle:User
            property: username

    firewalls:
        default:
            anonymous: ~
            saml:
                username_attribute: uid
                check_path: /saml/acs
                login_path: /saml/login
                failure_path: /login
                always_use_default_target_path: true
                
            # Traditional login form
            form_login:
                login_path: /login
                check_path: /login_check
                
            logout:
                path: /saml/logout
```
