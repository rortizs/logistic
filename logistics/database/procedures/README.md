# Logistics System Stored Procedures

This directory contains the essential stored procedures for the logistics system, designed to manage truck mileage, maintenance scheduling, and trip reporting.

## Installation

1. **Execute all procedures at once:**
   ```bash
   mysql -u root -p -D logistic -e "SOURCE install_procedures.sql"
   ```

2. **Execute individual procedures:**
   ```bash
   mysql -u root -p -D logistic -e "SOURCE 01_actualizar_kilometraje_camion.sql"
   mysql -u root -p -D logistic -e "SOURCE 02_verificar_mantenimiento_pendiente.sql"
   mysql -u root -p -D logistic -e "SOURCE 03_generar_reporte_viajes.sql"
   ```

3. **Test the procedures:**
   ```bash
   mysql -u root -p -D logistic -e "SOURCE test_data_and_procedures.sql"
   ```

## Available Procedures

### 1. ActualizarKilometrajeCamion(camion_id, kilometros_recorridos)

**Purpose:** Updates truck mileage and automatically schedules maintenance when needed.

**Parameters:**
- `camion_id` (INT): ID of the truck to update
- `kilometros_recorridos` (DECIMAL(10,2)): Kilometers to add to current mileage

**Features:**
- Updates the `kilometraje_actual` field in the `camiones` table
- Checks if maintenance is due based on `intervalo_mantenimiento_km`
- Automatically creates maintenance records when due
- Prevents duplicate maintenance scheduling
- Comprehensive error handling

**Usage from PHP (Laravel):**
```php
use Illuminate\Support\Facades\DB;

// Called from ViajeController when completing a trip
DB::statement('CALL ActualizarKilometrajeCamion(?, ?)', [
    $camion_id,
    $kilometros_recorridos
]);
```

**Example Call:**
```sql
CALL ActualizarKilometrajeCamion(1, 250.5);
```

**Returns:**
- Success status with mileage information
- Maintenance scheduling status
- Detailed messages

---

### 2. VerificarMantenimientoPendiente(camion_id)

**Purpose:** Checks maintenance status for a specific truck.

**Parameters:**
- `camion_id` (INT): ID of the truck to check

**Features:**
- Analyzes current mileage vs maintenance interval
- Calculates kilometers since last maintenance
- Provides maintenance alerts based on status
- Returns comprehensive truck and maintenance information
- JSON formatted output for easy parsing

**Usage:**
```sql
CALL VerificarMantenimientoPendiente(1);
```

**Returns:**
- Truck information (ID, plate, brand, model, status)
- Maintenance status (needed, kilometers until next, alerts)
- Maintenance history analysis
- Alert levels: URGENTE, ADVERTENCIA, INFO, OK

---

### 3. GenerarReporteViajes(fecha_inicio, fecha_fin)

**Purpose:** Generates comprehensive trip reports for a date range.

**Parameters:**
- `fecha_inicio` (DATE): Start date for the report
- `fecha_fin` (DATE): End date for the report

**Features:**
- Complete trip details with truck, driver, and route information
- Statistics by trip status, truck, and driver
- Performance analysis (mileage vs route distance)
- Time calculations and efficiency metrics
- Multiple result sets for different report aspects

**Usage:**
```sql
CALL GenerarReporteViajes('2025-09-01', '2025-09-30');
```

**Returns:**
1. **Summary:** Total trips, kilometers, averages
2. **Detailed Trips:** Complete trip information with performance evaluation
3. **Statistics by Status:** Trip counts and totals by status
4. **Statistics by Truck:** Performance metrics per truck
5. **Statistics by Driver:** Performance metrics per driver

---

## Error Handling

All procedures include comprehensive error handling:
- Parameter validation
- Database constraint checking  
- Transaction rollback on errors
- Descriptive error messages
- Graceful failure handling

## Database Schema Dependencies

These procedures work with the following tables:
- `camiones` (trucks)
- `pilotos` (drivers)  
- `rutas` (routes)
- `viajes` (trips)
- `mantemientos` (maintenance)

## Integration with Laravel

The procedures are designed to work seamlessly with the existing Laravel application:

1. **ViajeController** already calls `ActualizarKilometrajeCamion`
2. **Error handling** compatible with Laravel's exception handling
3. **JSON responses** easy to parse in PHP
4. **Transaction safety** ensures data consistency

## Performance Considerations

- All procedures use proper indexes
- Efficient queries with minimal table scans
- Transaction boundaries minimize lock time
- Parameterized queries prevent SQL injection

## Maintenance Schedule Logic

The maintenance scheduling follows this logic:
1. Calculate kilometers since last completed maintenance
2. Compare with truck's maintenance interval
3. If due, create a "Preventivo" maintenance record
4. Schedule maintenance for 7 days from current date
5. Prevent duplicate scheduling

## Testing

The `test_data_and_procedures.sql` file provides:
- Sample data for all related tables
- Test cases for each procedure
- Error condition testing
- Expected output validation

Run the test script to verify proper installation and functionality.

## File Structure

```
database/procedures/
├── README.md                              # This documentation
├── install_procedures.sql                 # Install all procedures
├── 01_actualizar_kilometraje_camion.sql  # Mileage update procedure
├── 02_verificar_mantenimiento_pendiente.sql # Maintenance check procedure
├── 03_generar_reporte_viajes.sql         # Trip reporting procedure
└── test_data_and_procedures.sql          # Test data and examples
```

## Security Notes

- All procedures use parameterized inputs
- Input validation prevents invalid data
- Transaction boundaries ensure data consistency
- Error messages don't expose sensitive information
- Proper privilege management recommended