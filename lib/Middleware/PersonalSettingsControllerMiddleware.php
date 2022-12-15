<?php

namespace OCA\Keycloak\Middleware;

use OCA\Settings\Controller\PersonalSettingsController;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Http\Response;
use OC;

class PersonalSettingsControllerMiddleware extends Middleware {

	public function afterController($controller, $methodName, Response $response): Response {
		$server = OC::$server;

		if (!
			($controller instanceof PersonalSettingsController &&
			$server->getRequest()->getParams()['_route'] == 'settings.PersonalSettings.index' &&
			$server->getRequest()->getParams()['section'] == 'security')
		) {
			return $response;
		}

		if (
			$server->getUserSession()->isLoggedIn() &&
			$server->getUserSession()->getUser()->getBackendClassName() == 'user_saml'
		) {
			$params = $response->getParams();
			$content = array_map('trim', array_filter(explode("\n", $params['content'])));

			// Remove WebAuthn and authtokens (won't work with this Keycloak config)
			$content = array_diff($content, array(
				'<div id="security-webauthn" class="section"></div>',
				'<div id="security-authtokens" class="section"></div>',
			));

			// Add a link to the Keycloak change password page (this should really be in a `user_saml` extension plugin)
			// This should provide a way to get a list of IdPs and their IDs, but not sure
			//OC::$server->get('OCA\User_SAML\SAMLSettings')->getListOfIdps();
			$idpProperties = OC::$server->get('OCA\User_SAML\SAMLSettings')->get(1);
			$idpUrl = $idpProperties['idp-entityId'];

			if ($idpUrl) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $idpUrl);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

				$result = curl_exec($ch);
				if (curl_errno($ch)) {
					echo 'Error:' . curl_error($ch);
				}
				else {
					$json = json_decode($result, true);

					if (
						is_array($json) &&
						(isset($json['account-service']) || array_key_exists('account-service', $json))
					) {
						$passwordBlock = '
						<div id="security-password" class="section">
							<h2 class="inlineblock">Password</h2>
							<div class="personal-settings-setting-box personal-settings-password-box">
								<a target="_blank" href="' . $json['account-service'] . '/#/security/signingin">
									<input id="passwordbutton" type="submit" value="Change password">
								</a>
							</div>
						</div>';

						$index = array_search('<div id="security-password"></div>', $content);
						$content[$index] = $passwordBlock;
					}
				}
				curl_close($ch);
			}

			$params['content'] = implode("\n", $content);
			$response->setParams($params);
		}

		return $response;
	}

	public function beforeOutput($controller, $methodName, $output){
		return $output;
	
	//	$server = OC::$server;
	//
	//	if (! 
	//		($controller instanceof PersonalSettingsController &&
	//		$server->getRequest()->getParams()['_route'] == 'settings.PersonalSettings.index' &&
	//		$server->getRequest()->getParams()['section'] == 'personal-info')
	//	) {
	//	}
	}
}