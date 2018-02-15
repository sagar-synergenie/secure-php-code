<?php
// *** Rewritten using PDO, parameterized queries, and prepared statements
// PHP and MySQL Project
// products table data class

class Products
{
	public $companyName = 'Sweets Complete';
	public $page 		= 'Home';
	public $debug		= TRUE;
	public $productsPerPage = 9;
	public $howManyProducts = 0;

	protected $pdo = NULL;

	public function __destruct()
	{
		if ($this->pdo) {
			$this->pdo = NULL;
		}
	}

	/*
	 * Returns database row for $productsPerPage number of products
	 * @param int $offset
	 * @return array(array $row[] = array('title' => title, 'description' => description, etc.))
	 */
	public function getProducts($offset = 0)
	{
		$content = array();
		$pdo = $this->getPdo();
		$sql = 'SELECT * FROM `products` '
			  . 'ORDER BY `title` '
			  . 'LIMIT ' . $this->productsPerPage . ' '
			  . 'OFFSET ' . (int) $offset;
		try {
			$stmt = $pdo->query($sql);
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$content[] = $row;
			}
		} catch (PDOException $e) {
				error_log($e->getMessage, 0);
				$_SESSION['error'] = 'Database error';
				header('Location: ' . HOME_URL . '?page=error');
				exit;
		}
		return $content;
	}
	/*
	 * Returns database rows for all products
	 * @return array(array $row[] = array('title' => title, 'description' => description, etc.))
	 */
	public function getAllProducts()
	{
		$content = array();
		$pdo = $this->getPdo();
		$sql = 'SELECT * FROM `products`';
		$stmt = $pdo->query($sql);
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$content[] = $row;
		}
		return $content;
	}
	/*
	 * Returns an associative array with product_id as key and title as value for all products
	 * @return array['product_id'] = title
	 */
	public function getProductTitles()
	{
		$content = array();
		$pdo = $this->getPdo();
		$sql = 'SELECT `product_id`, `title` FROM `products`';
		try {
			$stmt = $pdo->query($sql);
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$content[$row['product_id']] = $row['title'];
			}
		} catch (PDOException $e) {
			error_log($e->getMessage, 0);
			$_SESSION['error'] = 'Database error';
			header('Location: ' . HOME_URL . '?page=error');
			exit;
		}
		asort($content, SORT_STRING);
		return $content;
	}
	/*
	 * Returns database row for 1 product
	 * @param int $id = product ID
	 * @return array $row[] = array('title' => title, 'description' => description, etc.)
	 */
	public function getDetailsById($id)
	{
		$pdo = $this->getPdo();
		$sql = 'SELECT * FROM `products` WHERE `product_id` = ?';
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute(array($id));
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			error_log($e->getMessage, 0);
			$_SESSION['error'] = 'Database error';
			header('Location: ' . HOME_URL . '?page=error');
			exit;
		}
		return $result;
	}
	/**
	 * Returns a count of how many products are in the products table
	 * @return int COUNT(*)
	 */
	public function getHowManyProducts()
	{
		if (!$this->howManyProducts) {
			$pdo = $this->getPdo();
			$sql = 'SELECT COUNT(*) FROM `products`';
			try {
				$stmt = $pdo->query($sql);
				// fetches as a numeric array
				$result = $stmt->fetch(PDO::FETCH_NUM);
			} catch (PDOException $e) {
				error_log($e->getMessage, 0);
				$_SESSION['error'] = 'Database error';
				header('Location: ' . HOME_URL . '?page=error');
				exit;
			}
			$this->howManyProducts = $result[0];
		}
		return $this->howManyProducts;
	}
	/*
	 * Returns array of arrays where each sub-array = 1 database row of products
	 * Returns only those products which are on special
	 * @param int $limit = how many specials to show
	 * @return array $row[] = array('title' => title, 'description' => description, etc.)
	 */
	public function getProductsOnSpecial($limit = 0)
	{
		$content = array();
		$pdo = $this->getPdo();
		$sql = 'SELECT * FROM `products` WHERE `special` = 1 ORDER BY `title`';
		$limit = (int) $limit;
		if ($limit) {
			$sql .= ' LIMIT ' . $limit;
		}
		try {
			$stmt = $pdo->query($sql);
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$content[] = $row;
			}
		} catch (PDOException $e) {
			error_log($e->getMessage, 0);
			$_SESSION['error'] = 'Database error';
			header('Location: ' . HOME_URL . '?page=error');
			exit;
		}
		return $content;
	}
	/*
	 * Returns array of arrays where each sub-array = 1 database row of products
	 * Searches title and description fields
	 * @param string $search
	 * @return array $row[] = array('title' => title, 'description' => description, etc.)
	 */
	public function getProductsByTitleOrDescription($search)
	{
		$content = array();
		// strip out any unwanted characters to help prevent SQL injection
		$search = str_ireplace(array('UNION','SELECT'), '', $search);
		$search = "'%" . preg_replace('/[^a-zA-Z0-9 ]/', '', $search) . "%'";
		$pdo = $this->getPdo();
		// use a prepared statement
		$sql = 'SELECT * FROM `products` WHERE `title` LIKE ? OR `description` LIKE ? ORDER BY `title`';
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute(array($search, $search));
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$content[] = $row;
			}
		} catch (PDOException $e) {
			error_log($e->getMessage, 0);
			$_SESSION['error'] = 'Database error';
			header('Location: ' . HOME_URL . '?page=error');
			exit;
		}
		return $content;
	}
	/*
	 * Returns all products in shopping cart from $_SESSION
	 * @return array $row[] = array('title' => title, 'description' => description, etc.)
	 */
	public function getShoppingCart()
	{
		$content = (isset($_SESSION['cart'])) ? $_SESSION['cart'] : array();
		return $content;
	}
	/*
	 * Adds purchase to basket
	 * @param int $id = product ID
	 * @param int $quantity
	 * @param float $price (NOTE: sale_price in the `purchases` table = $quantity * $price
	 * @return boolean $success
	 */
	public function addProductToCart($id, $quantity, $price)
	{
		$item = $this->getDetailsById($id);
		$item['qty'] 		= $quantity;
		$item['price']		= $price;
		$item['notes']		= 'Notes';
		$_SESSION['cart'][] = $item;
		return TRUE;
	}
	/*
	 * Removes purchase from basket
	 * @param int $productID
	 * @return boolean $success
	 */
	public function delProductFromCart($productID)
	{
		$removed = FALSE;
		if (isset($_SESSION['cart'])) {
			foreach ($_SESSION['cart'] as $key => $row) {
				if ($row['product_id'] == $productID) {
					unset($_SESSION['cart'][$key]);
					$removed = TRUE;
				}
			}
		}
		return $removed;
	}
	/*
	 * Updates purchase from basket
	 * @param int $productID
	 * @param string $notes
	 * @param int $qty
	 * @return boolean $success
	 */
	public function updateProductInCart($productID, $qty, $notes)
	{
		$updated = FALSE;
		if (isset($_SESSION['cart'])) {
			foreach ($_SESSION['cart'] as $key => $row) {
				if ($row['product_id'] == $productID) {
					$_SESSION['cart'][$key]['qty'] 	 = $qty;
					$_SESSION['cart'][$key]['notes'] = $notes;
					$updated = TRUE;
				}
			}
		}
		return $updated;
	}

	/*
	 * Returns a safely quoted value
	 * @param string $value
	 * @return string $quotedValue
	 */
	public function quoteValue($value)
	{
		$pdo = $this->getPdo();
		return $pdo->quote($value);
	}

	/**
	 * Returns a PDO object
	 * @throws Exception
	 * @return PDO $pdo
	 */
	public function getPdo()
	{
		if (!$this->pdo) {
			// *** display of warnings should be suppressed in production
			try {
				$this->pdo = new PDO(DB_DSN, DB_USER, DB_PWD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
			} catch (PDOException $e) {
				error_log($e->getMessage(), 0);
				$_SESSION['error'] = 'Database error';
				header('Location: ' . HOME_URL . '?page=error');
				exit;
			}
		}
		return $this->pdo;
	}

}
