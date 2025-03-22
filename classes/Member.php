<?php

class Member extends User {
	protected $miles;
	protected $user_id;

	public function __construct($database, $user_id, $miles = 0) {
		parent::__construct($database);
		$this->miles = $miles;
		$this->user_id = $user_id;
	}

	public function getMiles() { return $this->miles; }

	public function setMiles($points) { $this->miles = $points; }

	public function getUserId() { return $this->user_id; }

	public function setUserID($userid) { $this->user_id = $userid; }

	public function calculateMiles() {
		$stmt = $this->db->prepare("SELECT SUM(miles) AS total_miles FROM bookings WHERE user_id = ?");
		$stmt->execute([$this->user_id]);
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->miles = $result["total_miles"] ?? 0;
	}
}

?>