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
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$this->miles = $result["total_miles"] ?? 0;
	}

	public function getLevel() {
		if ($this->miles < 1000) return "Normal";
		if ($this->miles < 5000) return "Bronze";
		if ($this->miles < 10000) return "Silver";
		return "Gold";
	}

	public function getTransactions() {
		$stmt = $this->db->prepare("SELECT f.airline, f.flight_number, f.departure, f.arrival, b.miles, b.booking_date FROM bookings b JOIN flights f on b.flight_id = f.id WHERE user_id = ? ORDER BY booking_date DESC");
		$stmt->execute([$this->user_id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}

?>