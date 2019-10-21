<?php
use Zynga\Model\SessionProfile;

class Home extends MY_Controller
{
	/**
	 * @var SessionProfile
	 */
	private $session;

	public function __construct() {
		parent::__construct();
		$this->session = SessionProfile::GetInformation();
	}

	public function index() {
		if (!$this->session->authenticated) {
			redirect("/home/login");
		}

		$this->load->view("home_index");
	}

	public function login() {
		if ($this->session->authenticated) {
			redirect("/home");
		}

		$postUserName = (string)$this->input->post("username");
		$postPassword = (string)$this->input->post("password");
		$loginErrorMessage = "";
		if (!empty($postUserName) && !empty($postPassword)) {
			$loginResult = $this->session->TryAuthenticate($postUserName, $postPassword);
			if ($loginResult->Success) {
				redirect("/home");
			} else {
				$loginErrorMessage = $loginResult->Message;
			}
		}

		$this->session->GenerateLoginRandomSalt();
		$data = array(
			"username" => $postUserName,
			"loginErrorMessage" => $loginErrorMessage,
			"passwordSalt" => $this->session->loginPasswordSalt,
		);
		$this->load->view("home_login", $data);
	}
}