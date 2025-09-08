from flask import Flask, render_template, request
import requests

app = Flask(__name__)
MAIN_APP_URL = "http://127.0.0.1:5000/update_availability" 

EMPLOYEES = [
    {'id': 1, 'name': 'Ms Sakshi Sharma'},
    {'id': 2, 'name': 'Mr Ishit Chauhan'},
    {'id': 3, 'name': 'Mrs Priya Verma'},
    {'id': 4, 'name': 'Mr Ashish Choudhary'}
]

@app.route('/', methods=['GET', 'POST'])
def faculty_page():
    message = ''
    if request.method == 'POST':
        employee_id = request.form.get('employee_id')
        status = request.form.get('status')
        if employee_id and status:
            response = requests.post(MAIN_APP_URL, json={'employee_id': int(employee_id), 'status': status})
            if response.ok:
                message = 'Availability updated successfully!'
            else:
                message = 'Failed to update availability.'

    return render_template('faculty.html', employees=EMPLOYEES, message=message)

if __name__ == '__main__':
    app.run(port=5001, debug=True)
