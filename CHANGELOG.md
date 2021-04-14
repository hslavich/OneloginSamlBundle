# Changelog

## 2.1.0

 * Configuration format changed from yaml to php.
 * Services "legacy" aliases deprecated. Use classnames as service ids.
 * "Legacy" security listener/provider deprecated. Set "security.enable_authenticator_manager" to true to use SamlAuthenticator.
 * "hslavich_onelogin_saml.security.entityManagerName" option deprecated. Use "hslavich_onelogin_saml.entityManagerName" instead.
 * SamlAuthenticationSuccessHandler namespace standardized, old namespace deprecated.
 * Throw \RuntimeException instead of \Exception when username attribute not found in SAML response. 
