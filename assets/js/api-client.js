// filepath: /flight-booking-website/assets/js/api-client.js

const apiBaseUrl = 'https://api.example.com/flights'; // Replace with actual API URL

async function fetchFlightSchedules() {
    try {
        const response = await fetch(`${apiBaseUrl}/schedules`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching flight schedules:', error);
        throw error;
    }
}

async function bookFlight(flightData) {
    try {
        const response = await fetch(`${apiBaseUrl}/book`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(flightData),
        });
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error booking flight:', error);
        throw error;
    }
}

async function getFlightDetails(flightId) {
    try {
        const response = await fetch(`${apiBaseUrl}/details/${flightId}`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching flight details:', error);
        throw error;
    }
}