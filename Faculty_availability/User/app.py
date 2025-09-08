from flask import Flask, request, render_template
import sqlite3
from datetime import datetime

app = Flask(__name__)
DB = 'database.db'

def init_db():
    conn = sqlite3.connect(DB)
    c = conn.cursor()

   
    c.execute('''CREATE TABLE IF NOT EXISTS locations (
                 location_id INTEGER PRIMARY KEY AUTOINCREMENT,
                 building_name TEXT,
                 room_number TEXT,
                 floor TEXT,
                 lat REAL,
                 lng REAL)''')

    
    c.execute('''CREATE TABLE IF NOT EXISTS employees (
                 employee_id INTEGER PRIMARY KEY AUTOINCREMENT,
                 name TEXT,
                 department TEXT,
                 current_location_id INTEGER,
                 availability_status TEXT,
                 phone_number TEXT,
                 latitude REAL,
                 longitude REAL)''')

    
    c.execute('''CREATE TABLE IF NOT EXISTS timetable (
                 timetable_id INTEGER PRIMARY KEY AUTOINCREMENT,
                 employee_id INTEGER,
                 day_of_week TEXT,
                 start_time TEXT,
                 end_time TEXT)''')

    
    c.execute('SELECT COUNT(*) FROM locations')
    if c.fetchone()[0] == 0:
        locations = [
            ('Engineering Block', '101', '1', 26.9145, 75.7860),
            ('Engineering Block', '102', '1', 26.9146, 75.7861),
            ('Admin Block', '201', '2', 26.9150, 75.7870),
            ('Library', '301', '3', 26.9155, 75.7880)
        ]
        c.executemany('INSERT INTO locations (building_name, room_number, floor, lat, lng) VALUES (?, ?, ?, ?, ?)', locations)

    c.execute('SELECT COUNT(*) FROM employees')
    if c.fetchone()[0] == 0:
        employees = [
            ('Ms Sakshi Sharma', 'Computer Science', 1, 'Available', '9991110001', 26.8093, 75.5417),
            ('Mr Ishit Chauhan', 'Mechanical', 2, 'Busy', '9991110002',  26.8093, 75.5417),
            ('Mrs Priya Verma', 'Electrical', 3, 'Available', '9991110003',  26.8093, 75.5417),
            ('Mr Mr Ashish Choudhary', 'Civil', 4, 'Offline', '9991110004',  26.8093, 75.5417)
        ]
        c.executemany('''INSERT INTO employees 
                         (name, department, current_location_id, availability_status, phone_number, latitude, longitude) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)''', employees)

    c.execute('SELECT COUNT(*) FROM timetable')
    if c.fetchone()[0] == 0:
        timetable = [
            (1, 'Monday', '09:00', '11:00'),
            (1, 'Tuesday', '14:00', '16:00'),
            (2, 'Monday', '10:00', '12:00')
        ]
        c.executemany('INSERT INTO timetable (employee_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)', timetable)

    conn.commit()
    conn.close()

def update_availability_from_timetable():
    conn = sqlite3.connect(DB)
    c = conn.cursor()
    now = datetime.now()
    current_day = now.strftime('%A')
    current_time = now.strftime('%H:%M')
    
    c.execute('SELECT employee_id, start_time, end_time FROM timetable WHERE day_of_week=?', (current_day,))
    entries = c.fetchall()
    
    for emp_id, start, end in entries:
        if start <= current_time <= end:
            c.execute('UPDATE employees SET availability_status=? WHERE employee_id=?', ('Busy', emp_id))
    
    conn.commit()
    conn.close()

@app.route('/', methods=['GET', 'POST'])
def home():
    update_availability_from_timetable()
    conn = sqlite3.connect(DB)
    c = conn.cursor()
    message = ''

    if request.method == 'POST':
        employee_id = request.form.get('employee_id')
        location_id = request.form.get('location_id')
        if employee_id and location_id:
            c.execute('SELECT lat, lng FROM locations WHERE location_id=?', (location_id,))
            lat, lng = c.fetchone()
            c.execute('UPDATE employees SET current_location_id=?, latitude=?, longitude=? WHERE employee_id=?',
                      (location_id, lat, lng, employee_id))
            conn.commit()
            message = 'Location updated successfully!'

    query = request.args.get('q', '').lower()
    department = request.args.get('department', '')
    statuses = request.args.getlist('status')

    query_sql = '''SELECT e.employee_id, e.name, e.department, l.building_name, l.room_number, e.availability_status, e.phone_number,
                          l.lat, l.lng
                   FROM employees e LEFT JOIN locations l ON e.current_location_id = l.location_id
                   WHERE 1=1'''
    params = []

    if query:
        query_sql += ' AND (LOWER(e.name) LIKE ? OR LOWER(e.department) LIKE ?)'
        params.extend([f'%{query}%', f'%{query}%'])
    if department:
        query_sql += ' AND e.department = ?'
        params.append(department)
    if statuses:
        placeholders = ','.join('?' for _ in statuses)
        query_sql += f' AND e.availability_status IN ({placeholders})'
        params.extend(statuses)

    c.execute(query_sql, params)
    employees = c.fetchall()

    c.execute('SELECT location_id, building_name, room_number FROM locations')
    locations = c.fetchall()
    c.execute('SELECT employee_id, name FROM employees')
    all_employees = c.fetchall()

    conn.close()
    return render_template('index.html', employees=employees, query=query, message=message,
                           locations=locations, all_employees=all_employees)

# --- API endpoint for faculty app ---
@app.route('/update_availability', methods=['POST'])
def update_availability():
    data = request.json
    employee_id = data.get('employee_id')
    status = data.get('status')
    if not employee_id or not status:
        return {'success': False, 'message': 'Missing data'}, 400

    conn = sqlite3.connect(DB)
    c = conn.cursor()
    c.execute('UPDATE employees SET availability_status=? WHERE employee_id=?', (status, employee_id))
    conn.commit()
    conn.close()
    return {'success': True, 'message': 'Availability updated'}

if __name__ == '__main__':
    init_db()
    app.run(debug=True)
