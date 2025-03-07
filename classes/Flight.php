<?php

class Flight {
    private $flightNumber;
    private $departure;
    private $arrival;
    private $duration;
    private $price;

    public function __construct($flightNumber, $departure, $arrival, $duration, $price) {
        $this->flightNumber = $flightNumber;
        $this->departure = $departure;
        $this->arrival = $arrival;
        $this->duration = $duration;
        $this->price = $price;
    }

    public function getFlightNumber() {
        return $this->flightNumber;
    }

    public function getDeparture() {
        return $this->departure;
    }

    public function getArrival() {
        return $this->arrival;
    }

    public function getDuration() {
        return $this->duration;
    }

    public function getPrice() {
        return $this->price;
    }

    public function setFlightNumber($flightNumber) {
        $this->flightNumber = $flightNumber;
    }

    public function setDeparture($departure) {
        $this->departure = $departure;
    }

    public function setArrival($arrival) {
        $this->arrival = $arrival;
    }

    public function setDuration($duration) {
        $this->duration = $duration;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function toArray() {
        return [
            'flightNumber' => $this->flightNumber,
            'departure' => $this->departure,
            'arrival' => $this->arrival,
            'duration' => $this->duration,
            'price' => $this->price,
        ];
    }
}