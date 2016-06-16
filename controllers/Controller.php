<?php

class WP_REST_API_FB_Login_Controller {

	/**
	 * Versión del API
	 * @var string
	 */
	protected $version = '1';

	/**
	 * Namespace a utilizar por los endpoints del tema
	 * @var string
	 */
	protected $namespace = 'royale/v';

	/**
	 * Usuario de worpdress
	 * @var WP_User
	 */
	protected $wp_user;

	public $payload;

	// protected $request;
	/**
	 * Adiere de manera predeterminada las rutas a registrar
	 */
	function __construct(){
		@set_exception_handler([$this, 'exception_handler']);
		add_action( 'rest_api_init', [$this, 'register_routes'] );
	}

	/**
	 * Funcion temporal para definir todos los enpoints incompletos
	 * @param  WP_REST_Request $request 
	 * @return array         
	 */
	public function incomplete( WP_REST_Request $request ) {

		$response = [
			'message' => 'Endpoint not ready for testing',
			'error' => 'endpoint_not_available',
			'params' => $request->get_params()
		];

		return new WP_REST_Response( $response, 501 );
	}

	/**
	 * Autenticación minima para saber si el usuario está autorizado
	 * @return bool true|false
	 */
	public function get_basic_auth(){
		return current_user_can( 'read' );
	}

	/**
	 * Determina si el app y el usuario están logueados
	 * @param $request 	WP_REST_Request	Es el request con los paramentros del body
	 * @return (bool) 
	 */
	public function get_user_and_app_auth( WP_REST_Request $request ){
		if ( ! current_user_can( 'read' ) ) {
			return false;
		}
		
		if ( ! $this->get_if_user_logged( $request->get_param('token') ) ) {
			return false;
		}

		return user_can( $this->wp_user, 'read' );
	}

	/**
	 * Verifica si un usuario está logueado
	 * @param string $token Access token utilizado por el app para saber si un usuario está logueado
	 * @param bool $set_user Si es true setea el usuario en la variable $this->wp_user
	 * @return bool 
	 */
	protected function get_if_user_logged( $token = '', $set_user = true ){
		if ( ! $token ) {
			return false;
		}

		$payload = $this->get_payload( $token );
		
		if ( ! isset($payload->jti ) ) {
			return false;
		}

		if (! $user_id = wp_cache_get( 'access_token:' . $payload->jti, 'users' ) ) {
			return false;			
		} 

		if ($user_id != $payload->data->userId) {
			return false;
		}

		if ($set_user) {
			$this->wp_user = get_user_by( 'ID', $user_id);
		}
		return true;
	}

	/**
	 * Retorna el payload del token (La información del token)
	 * @param string $token Token JWT
	 * @param bool $set_payload Si es true setea el payload en la clase
	 * @return object Payload en formato stdClass
	 */
	protected function get_payload( $token = '' , $set_payload = true){
		$payload = \Firebase\JWT\JWT::decode( $token, AUTH_KEY, ['HS256'] );
		if ($set_payload = true) {
			$this->payload = $payload;
		}
		return $payload;
	}

	/**
	 * Manejador de excepciones de Controller y sus derivados
	 * @param Exception $exception ID de la excepción lanzada
	 * @return void
	 */
	public function exception_handler( $exception ){
		if ($exception instanceof \Facebook\Exceptions\FacebookResponseException) {
			$this->render([
				'error' => 'facebook_graph_error', 
				'message' => $exception->getMessage()
			], 400);
		}

		if ($exception instanceof \Facebook\Exceptions\FacebookSDKException) {
			$this->render([
				'error' => 'facebook_sdk_error', 
				'message' => $exception->getMessage()
			], 500);
		}

		if ($exception instanceof \UnexpectedValueException) {
			$this->render([
				'error' => 'unexpected_value_error', 
				'message' => $exception->getMessage()
			], 500);
		}

		if ($exception instanceof \DomainException) {
			$this->render([
				'error' => 'domain_error', 
				'message' => $exception->getMessage()
			], 500);
		}
		
		
		$this->render([
			'code' => 'random_error', 
			'message' => $exception->getMessage()
		], 500);
	}

	/**
	 * Envia respuesta en formato json al cliente
	 * @param array|null $data Campos a enviar por json_encode
	 * @param integer $status Estatus que tendrá la respuesta
	 * @return json array|null 
	 */
	protected function render( $data, $status = 200 ){
		header('Content-Type: application/json');
		http_response_code($status);
		echo json_encode($data);
		die();
	} 
}