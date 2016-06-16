<?php
/**
* Authclass
*/
class WP_REST_API_FB_Login_AuthController extends WP_REST_API_FB_Login_Controller
{
	private $fb;
	
	private $fbUser;

	private $user;
	
	private $facebookLongtoken;

	private $accessToken;

	/**
	 * Registra las rutas del controller
	 */
	public function register_routes(){
		$namespace = $this->namespace . $this->version;
		$base = '/auth';

		register_rest_route( $namespace,  $base . '/login', [
			[
				'methods' => WP_REST_Server::CREATABLE,
				'permission_callback' => [$this, 'get_basic_auth'],
				'callback' => [$this, 'post_login'],
				'args' => [
					'access_token' => [
						'required' => true
					]
				]
			],
			[
				'methods' => WP_REST_Server::DELETABLE,
				'permission_callback' => [$this, 'get_user_and_app_auth'],
				'callback' => [$this, 'delete_login'],
				'args' => [
					'token' => [
						'required' => true
					]
				]
			]
		]);
	}

	/**
	 * Recibe la peticion desde POST /auth/login y procesa la informacion para saber si esta o no en la plataforma y crea un nuevo token de comunicacion para demostrar que el usuario esta logueado.
	 * @param  WP_REST_Request $request 	Peticion del usuario
	 * @return WP_REST_Response (array, integer)  Respuesta al request hecho por el usuario
	 */
	public function post_login( WP_REST_Request $request ){
		
		$this->isFacebookTokenAuthorizate( $request->get_param('access_token') );

		$this->user = $this->createOrLogin();

		$this->setTokens( $request->get_param('access_token') );

		return new WP_REST_Response( [
			'user' => $this->user,
			'token' => $this->accessToken
		], ($this->user['new']) ? 201 : 200 );
	}

	/**
	 * Ve si el access token entregado por el usuario es correcto, si lo es coloca el fbUser
	 * @param $facebookToken string 	Es el token enviado desde la aplicacion al server
	 */
	private function isFacebookTokenAuthorizate( $facebookToken = '' ){
		$this->fb = new Facebook\Facebook(require WP_REST_API_FB_LOGIN_PLUGIN_DIR . 'includes/facebook_config.php');

		$response = $this->fb->get('/me?fields=name,email,first_name,last_name,link', $facebookToken);
		$this->fbUser = $response->getGraphUser();
	}

	/**
	 * Funcion que crea o actualiza a un usuario
	 * @return WP_User 
	 */
	private function createOrLogin(){
		$user = $this->fbUser;

		$userdata = [
			'user_login' => $user->getId(),
			'display_name' => $user->getName(),
			'user_url' => $user->getLink(),
			'first_name' => $user->getFirstName(),
			'last_name' => $user->getLastName(),
			'role' => 'subscriber',
			'user_pass' => base64_encode(random_bytes(32)),
			'user_email' => $user->getProperty('email'),
			'new' => false
		];

		if ( $wpUser = get_user_by( 'login', $user->getId()) ) {
			$userdata['ID'] = (int) $wpUser->get('ID');
			$user_id = wp_update_user( $userdata );
		} else {
			$userdata['new'] = true;
			$user_id = wp_insert_user( $userdata );
		}

		if ( is_wp_error( $user_id ) ) {
			throw new \Exception($user_id->get_error_message());
		} else {
			$userdata['ID'] = $user_id;
		}

		unset($userdata['user_pass']);

		return $userdata;
	}

	/**
	 * Guarda en cache el token de larga vida del usuario
	 * @param $accessToken string Token Temporal de Facebook
	 * @return string
	 */
	private function setTokens( $accessToken = '' ){
		$oAuth2Client = $this->fb->getOAuth2Client();
		$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken( $accessToken );
		$this->facebookLongtoken = $longLivedAccessToken->getValue();

		$this->createNewAccessToken();
	}

	/**
	 * Crea un token de acceso nuevo para no usar el de facebook
	 * @return string 	 	Retorna el token 
	 */
	private function createNewAccessToken () {
		$token = md5(random_bytes(32) . ':' . $this->user['ID'] . ':' . SECURE_AUTH_KEY. time());
		$tokenId = substr(strtr(base64_encode(hex2bin($token)), '+', '.'), 0, 44);

		$issuedAt   = time();
	    $notBefore  = $issuedAt + 10;             //Adding 10 seconds
	    $expire     = $notBefore + (60*60);            // Adding 60 seconds

        $data = [
	        'iat'  => $issuedAt,         // Issued at: time when the token was generated
	        'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
	        'iss'  => 'http://hacienda.local',       // Issuer
	        'exp'  => $expire,           // Expire
	        'data' => [                  // Data related to the signer user
	            'userId'   => $this->user['ID'], // userid from the users table
	        ]
	    ];

	    $this->accessToken = \Firebase\JWT\JWT::encode($data, AUTH_KEY, 'HS256');
		
		wp_cache_set( 'access_token:' . $tokenId, $this->user['ID'], 'users', (60*60*24*50) );
		update_user_meta( $this->user['ID'], 'long_live_token', $this->facebookLongtoken );
	}

	/**
	 * Elimina el token del cache y devuelve si lo hizo o no
	 * @param WP_REST_Request $request Objeto request
	 * @return WP_REST_Response Respuesta que recibe array y status
	 */
	public function delete_login( WP_REST_Request $request ){
		return new WP_REST_Response( [
			'success' => (bool) wp_cache_delete( 'access_token:' . $this->payload->jti , 'users' )
		], ($this->user['new']) ? 201 : 200 );

	}
}

$AuthController = new WP_REST_API_FB_Login_AuthController();