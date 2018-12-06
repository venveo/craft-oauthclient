/**
 * OAuth 2.0 Client plugin for Craft CMS
 *
 * OAuth 2.0 Client JS
 *
 * @author    Venveo
 * @copyright Copyright (c) 2018 Venveo
 * @link      https://venveo.com
 * @package   Oauth20Client
 * @since     1.0.0
 */

/** global: Craft */
/** global: Garnish */
/**
 * OAuth String Generator
 */
// Craft.OAuthRedirectURIGenerator = Craft.BaseInputGenerator.extend(
//     {
//         generateTargetValue: function(sourceVal) {
//             // Remove HTML tags
//             var handle = sourceVal.replace("/<(.*?)>/g", '');
//
//             // Remove inner-word punctuation
//             handle = handle.replace(/['"‘’“”\[\]\(\)\{\}:]/g, '');
//
//             // Make it lowercase
//             handle = handle.toLowerCase();
//
//             // Convert extended ASCII characters to basic ASCII
//             handle = Craft.asciiString(handle);
//
//             // Handle must start with a letter
//             handle = handle.replace(/^[^a-z]+/, '');
//
//             // Get the "words"
//             var words = Craft.filterArray(handle.split(/[^a-z0-9]+/));
//             handle = '';
//
//             // Make it camelCase
//             for (var i = 0; i < words.length; i++) {
//                 if (i === 0) {
//                     handle += words[i];
//                 }
//                 else {
//                     handle += words[i].charAt(0).toUpperCase() + words[i].substr(1);
//                 }
//             }
//
//             return handle;
//         }
//     });