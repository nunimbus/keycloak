<?php
/**
 * @copyright Copyright (c) 2022, Andrew Summers
 *
 * @author Andrew Summers
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Keycloak\AppInfo;

use OCP\IRequest;
use OCP\AppFramework\App;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OC;

// Needed to register middleware
use OCP\AppFramework\QueryException;
use OC\AppFramework\DependencyInjection\DIContainer;

// Middleware
use OCA\Keycloak\Middleware\PersonalSettingsControllerMiddleware;

// Events
//use OCA\Keycloak\Listener\KeycloakUserLoggedInListener;

// Event listeners
//use OCP\User\Events\UserLoggedInEvent;


class Application extends App implements IBootstrap {

	public const APP_ID = 'keycloak';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$server = \OC::$server;

		// Register middleware to the "settings" app
		try {
			$settingsContainer = $server->getRegisteredAppContainer('settings');
		}
		catch (QueryException $e) {
			$server->registerAppContainer('settings', new DIContainer('settings'));
			$settingsContainer = $server->getRegisteredAppContainer('settings');
		}

		$settingsContainer->registerService('Keycloak\PersonalSettingsControllerMiddleware', function($c){
			return new PersonalSettingsControllerMiddleware(
				$c->get(IRequest::class),
				$c->get(IControllerMethodReflector::class)
			);
		});
		$settingsContainer->registerMiddleware('Keycloak\PersonalSettingsControllerMiddleware');
	}

	public function register(IRegistrationContext $context): void {
		//$context->registerEventListener(UserLoggedInEvent::class, KeycloakUserLoggedInListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}