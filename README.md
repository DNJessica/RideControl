# 🎢 RideControl

RideControl is a backend system built with CodeIgniter 4 for managing amusement park rides. It provides API endpoints to monitor the number of available personnel and clients, validate operational status, and manage wagons per ride.

## ⚙️ Technologies Used
- CodeIgniter 4 (PHP framework)
- RESTful API design
- Composer for dependency management

## 🚀 Features
- Add/update/delete coaster rides and wagons
- Verify if a ride has enough personnel and clients to operate
- JSON-based API structure
- Environment configuration using `.env` file

## 📡 API Reference

### ▶ Create Coaster
`POST /api/coasters`  
Creates a new coaster with data:
```json
{
  "liczba_personelu": 4,
  "liczba_klientow": 20,
  "dl_trasy": 250,
  "godziny_od": "10:00",
  "godziny_do": "18:00"
}
```

### ▶ Update Coaster
`PUT /api/coasters/{id}`  
Updates an existing coaster.

### ▶ Add Wagons to Coaster
`POST /api/coasters/{coaster_id}/wagons`  
Adds wagons to the specified coaster.

### ▶ Delete Wagon from Coaster
`DELETE /api/coasters/{coaster_id}/wagons/{wagon_id}`  
Removes a specific wagon from the coaster.

### ▶ Check if Enough Personnel
`GET /api/coasters/{coaster_id}/check-personel`  
Returns whether there is enough staff assigned to the coaster.

### ▶ Check if Enough Clients
`GET /api/coasters/{coaster_id}/check-clients`  
Returns whether there are enough clients for the ride to start.

## 📁 Project Structure
- `app/Controllers/` – Contains Coaster and Wagon controllers.
- `app/Models/` – Business logic and data layer.
- `app/Config/Routes.php` – Route definitions.

## 🧪 Development Setup
```bash
composer install
php spark serve
```

---

Project developed by [DNJessica](https://github.com/DNJessica)