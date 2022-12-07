# OneloginSamlBundle

!!! **This is a fork from [hslavich/OneloginSamlBundle](https://github.com/hslavich/OneloginSamlBundle)** check this
repo for more information.


## Fork changes

### Configuration - add multiple IDP support

Configure SAML metadata in `config/packages/hslavich_onelogin_saml.yaml`. Check https://github.com/onelogin/php-saml#settings for more info.
``` yml
hslavich_onelogin_saml:
    onelogin_settings:
        default:
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
        # Optional another one SAML settings (see Multiple IdP below)
        another:
            idp:
                # ...
            sp:
                # ...
            # ...
    # Optional parameters
    use_proxy_vars: true
    idp_parameter_name: 'custom-idp'
    entity_manager_name: 'custom-em'
```

### Multiple IdP

You can configure more than one OneLogin PHP SAML settings for multiple IdP. To do this you need to
specify SAML settings for each IdP (sections with `default` and `another` keys in configuration
above) and pass the name of the necessary IdP by a query string parameter `idp` or a request
attribute with the same name. You can use another name with help of `idp_parameter_name` bundle
parameter.

> To use appropriate SAML settings, all requests to bundle routes should contain correct IdP
> parameter.

If a request has no query parameter or attribute with IdP value, the first key
in `onelogin_settings` section will be used as default IdP.

### Using reverse proxy

When you use your application behind a reverse proxy and use `X-Forwarded-*` headers, you need to
set parameter `nbgrp_onelogin_saml.use_proxy_vars = true` to allow underlying OneLogin library
determine request protocol, host and port correctly.
