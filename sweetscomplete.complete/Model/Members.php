<?php
// *** sql injection: Even though this uses PDO, there are many badly written statements + unfiltered parameters
// PHP and MySQL Project
// members table data class

class Members
{
	public $debug = TRUE;
	protected $db_pdo;
	public $membersPerPage = 12;
	public $howManyMembers = 0;

	/*
	 * Returns array of arrays where each sub-array = 1 database row of Members
	 * @param int $offset [optional]
	 * @return array $row[] = array('title' => title, 'description' => description, etc.)
	 */
	public function getAllMembers($offset = 0)
	{
		$pdo = $this->getPdo();
		// *** filter $offset by setting data type to int
		$sql = 'SELECT `user_id`,`photo`,`name`,`city`,`email` '
			 . 'FROM `members` '
			 . 'ORDER BY `name` '
			 . 'LIMIT ' . $this->membersPerPage . ' '
			 . 'OFFSET ' . (int) $offset;
		$stmt = $pdo->query($sql);
		$content = array();
		// use FETCH_ASSOC to get more precision
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$content[] = $row;
		}
		return $content;
	}
	/*
	 * Returns database row for 1 member
	 * @param int $id = member ID
	 * @return array $row[] = array('title' => title, 'description' => description, etc.)
	 */
	public function getDetailsById($id)
	{
		$pdo = $this->getPdo();
		// use parameterized query with a prepared statement
		$sql = 'SELECT * FROM `members` WHERE `user_id` = ?';
		$stmt = $pdo->prepare($sql);
		$stmt->execute(array((int) $id));
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	/*
	 * Returns database row for 1 member
	 * @param string $email
	 * @return array $row[] = array('title' => title, 'description' => description, etc.)
	 */
	public function loginByName($email, $password)
	{
		$pdo = $this->getPdo();
		// use parameterized query with a prepared statement
		$sql = "SELECT * FROM `members` WHERE `email` = :email AND `password` = :password";
		$stmt = $pdo->prepare($sql);
		$stmt->execute(array('email' => $email, 'password' => hash('ripemd256', $password)));
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	public function getHowManyMembers()
	{
		if (!$this->howManyMembers) {
			$pdo = $this->getPdo();
			$sql = 'SELECT COUNT(*) FROM `members`';
			$stmt = $pdo->query($sql);
			// fetches as a numeric array
			$result = $stmt->fetch(PDO::FETCH_NUM);
			$this->howManyMembers = $result[0];
		}
		return $this->howManyMembers;
	}
	/*
	 * Returns array of arrays where each sub-array = 1 database row of Members
	 * Searches name, address, city, state_province, country, email
	 * @param string $search
	 * @return array $row[id] = array('title' => title, 'description' => description, etc.)
	 */
	public function getMembersByKeyword($search)
	{
		$content = array();
		$result = array();
		// filtering on $search to safeguard against SQL injection
		$search = str_ireplace(array('UNION','SELECT'), '', $search);
		$search = "'%" . preg_replace('/[^a-zA-Z0-9 ]/', '', $search) . "%'";
		$pdo = $this->getPdo();
		// use parameterized query with a prepared statement
		$sql = 'SELECT `user_id`,`photo`,`name`,`city`,`email` FROM `members` WHERE '
			  . '`name`  LIKE :name OR '
			  . '`city`  LIKE :city OR '
			  . '`email` LIKE :email '
			  . 'ORDER BY `name`';
		$stmt = $pdo->prepare($sql);
		$stmt->execute(array('name' => $search, 'city' => $search, 'email' => $search));
		// *** sql injection: use FETCH_ASSOC to get more precision
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$content[] = $row;
		}
		return $content;
	}
	/**
	 * Adds member to database
	 * @param array $data
	 * @return array(new member id, confirmation code)
	 */
	public function add($data)
	{
		// manage data
		$id 				= 0;
		$confirmCode 		= md5(date('YmdHis') . rand(1,9999));
		$data['name'] 		= $data['firstname'] . ' ' . $data['lastname'];
		$data['password'] 	= hash('ripemd256', $data['password']);
		$data['balance'] 	= 0;
		$data['status']		= 0;
		$data['code']		= $confirmCode;
		unset($data['firstname'],
			  $data['lastname'],
			  $data['dobyear'],
			  $data['dobmonth'],
			  $data['dobday'],
			  $data['submit']);
		// build sql
		$sql = 'INSERT INTO `members` ('
			 . '`name`,	`address`, `city`, `state_province`, `country`, `postal_code`, `phone`, '
			 . '`email`, `dob`, `photo`, `password`, `balance`, `status`, `confirm_code` ) '
			 . 'VALUES ('
			 . ':name, :address, :city, :stateProv, :country, :postcode, :telephone, '
			 . ':email, :dob, :photo, :password, :balance, :status, :code )';
		$pdo = $this->getPdo();
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($data);
			// get last insert id
			$id = $pdo->lastInsertId();
		} catch (PDOException $e) {
			error_log($e->getMessage, 0);
			$_SESSION['error'] = 'Database error';
			header('Location: ' . HOME_URL . '?page=error');
			exit;
		}
		return array($id, $confirmCode);
	}
	/**
	 * Adds member to database (from admin page)
	 * @param array $data
	 * @return boolean $success
	 */
	public function adminAdd($data)
	{
		$result = 0;
		// manage data
		unset($data['submit'], $data['update']);
		// build sql
		$data['password'] = hash('ripemd256', $data['password']);
		$sql = 'INSERT INTO `members` SET ';
		$sql .= "`user_id` 			= :user_id,";
		$sql .= "`name` 			= :name,";
		$sql .= "`address` 			= :address,";
		$sql .= "`city` 			= :city,";
		$sql .= "`state_province`	= :stateProv,";
		$sql .= "`country` 			= :country,";
		$sql .= "`postal_code` 		= :postcode,";
		$sql .= "`phone` 			= :telephone,";
		$sql .= "`email` 			= :email,";
		$sql .= "`dob` 				= :dob,";
		$sql .= "`photo` 			= :photo,";
		$sql .= "`password` 		= :password',";
		$sql .= "`balance` 			= :balance";
		$pdo = $this->getPdo();
		$result = FALSE;
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($data);
			// get last insert id
			$result = $pdo->lastInsertId();
		} catch (PDOException $e) {
			error_log($e->getMessage, 0);
			$_SESSION['error'] = 'Database error';
			header('Location: ' . HOME_URL . '?page=error');
			exit;
		}
		return $result;
	}
	/**
	 * Sends out email confirmation
	 * @param int $newId
	 * @param array $data
	 * @param string $confirmCode
	 * @return string $mailStatus
	 */
	public function confirm($newId, $data, $confirmCode)
	{
		// predictable resource: PHPMailer directory renamed to "Mail"
		require_once __DIR__ . '/../Mail/class.phpmailer.php';
		$link 	 = sprintf('<a href="%s?page=confirm&id=%s&code=%s">CLICK HERE</a>', HOME_URL, $newId, $confirmCode);
		$address = "info@sweetscomplete.com";
		$newName = $data['firstname'] . ' ' . $data['lastname'];
		$mail 	 = new PHPMailer(); // defaults to using php "mail()"
		$body 	 = 'Welcome to SweetsComplete ' . $newName . '!'
				 . '<br />To confirm your membership just reply to this email and we\'ll do the rest.'
				 . '<br />'
				 . $link
				 . 'to confirm your new membership account.'
				 . '<br />Here is your confirmation code just in case: ' . $confirmCode
				 . '<br />Happy eating!';
		$mail->AddReplyTo($address,"SweetsComplete");
		$mail->SetFrom($address,"SweetsComplete");
		$mail->AddAddress($data['email'], $newName);
		$mail->AddBCC($address,"SweetsComplete");
		$mail->Subject = 'SweetsComplete Membership Confirmation';
		$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
		$mail->MsgHTML($body);
		if(!$mail->Send()) {
			$mailStatus = 'Sorry: problem sending the email!';
			error_log($mail->ErrorInfo, 0);
			error_log($link, 0);
		} else {
			$mailStatus = 'Confirmation Email Message sent!';
		}
		return $mailStatus;
	}
	/*
	 * Confirms membership based on ID and confirmation code
	 * @param int $id = member ID
	 * @return boolean
	 */
	public function finishConfirm($id)
	{
		$pdo = $this->getPdo();
		// use parameterized query with a prepared statement
		$sql = 'UPDATE `members` SET '
			 . '`status` = 1, '
			 . '`confirm_code` = 1 '
			 . 'WHERE `user_id` = :id';
		$stmt = $pdo->prepare($sql);
		return $stmt->execute(array('id' => $id));
	}
	/*
	 * Returns a safely quoted value
	 * @param string $value
	 * @return string $quotedValue
	 */
	public function pdoQuoteValue($value)
	{
		$pdo = $this->getPdo();
		return $pdo->quote($value);
	}
	/*
	 * Returns a PDO connection
	 * If connection already made, returns that instance
	 * @return PDO $pdo
	 */
	public function getPdo()
	{
		// *** Need to turn off error mode in production
		if (!$this->db_pdo) {
			$this->db_pdo = new PDO(DB_DSN, DB_USER, DB_PWD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
		}
		return $this->db_pdo;
	}

}
