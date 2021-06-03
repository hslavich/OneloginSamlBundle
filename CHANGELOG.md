# Changelog

## 2.3.1

 * Re-implement UserProviderInterface::loadUserByUsername (see symfony/symfony#41493).

## 2.3.0

 * v2.3 dependencies upped to Symfony 5.3 with resolve deprecations.
 * Use UserBadge in SamlPassport instead of deprecated UserInterface.

## 2.2.2

 * v2.2 held on Symfony 5.2.
 * Added "security.allowRepeatAttributeName" config option.

## 2.2.1

 * v2.2 dependencies upped to Symfony 5.2.

## 2.2.0

 * EntryPointFactoryInterface was removed in Symfony 5.2.
 * SamlAuthenticator implements AuthenticationEntryPointInterface.

## 2.1.4

 * v2.1 held on Symfony 5.1.

## 2.1.3

 * "saml_acs" route allow POST request only.

## 2.1.2

 * Fixed SamlLogoutListener and introduced SamlPassport.

## 2.1.1

 * Added "idp.singleLogoutService.responseUrl" config option.

## 2.1.0

 * Configuration format changed from yaml to php.
 * Services "legacy" aliases deprecated. Use classnames as service ids.
 * "Legacy" security listener/provider deprecated. Set "security.enable_authenticator_manager" to true to use SamlAuthenticator.
 * "hslavich_onelogin_saml.security.entityManagerName" option deprecated. Use "hslavich_onelogin_saml.entityManagerName" instead.
 * SamlAuthenticationSuccessHandler namespace standardized, old namespace deprecated.
 * Throw \RuntimeException instead of \Exception when username attribute not found in SAML response. 
