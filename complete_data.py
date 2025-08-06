import random
import mysql.connector
import bcrypt
from faker import Faker
from datetime import datetime

# Initialize Faker for generating fake data. 'en_US' specifies the locale for English (United States) data.
fake = Faker('en_US')

# Establish a connection to the MySQL database.
# The connection parameters are hardcoded here: host, user, password (empty for no password), and database name.
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="escom_schedule"
)
# Create a cursor object, which allows Python code to execute MySQL commands.
cursor = conn.cursor()

# --- Configuration ---
# Define constants and lists for data generation, making the script configurable.

# List of predefined career names and their acronyms.
CARRERAS = [
    ("Ingeniería en Sistemas Computacionales", "C"),
    ("Ingeniería en Inteligencia Artificial", "I"),
    ("Licenciatura en Ciencia de Datos", "L")
]
# Number of subjects to generate per career.
MATERIAS_POR_CARRERA = 10
# Number of professors to generate per career.
PROFESORES_POR_CARRERA = 33
# Number of groups to generate per career.
GRUPOS_POR_CARRERA = 15
# List of days of the week for scheduling.
DIAS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
# List of time slots for classes, each as a tuple of (start_time, end_time).
HORAS = [
    ('07:00:00', '08:30:00'), ('08:30:00', '10:00:00'), ('10:30:00', '12:00:00'),
    ('12:00:00', '13:30:00'), ('13:30:00', '15:00:00'), ('15:00:00', '16:30:00'),
    ('16:30:00', '18:00:00'), ('18:30:00', '20:00:00'), ('20:00:00', '21:30:00')
]
# List of generic engineering-related subjects.
ENGINEERING_SUBJECTS = [
    "Programming Fundamentals", "Data Structures", "Algorithms", "Operating Systems",
    "Computer Networks", "Database Systems", "Artificial Intelligence", "Machine Learning",
    "Computer Architecture", "Software Engineering", "Cybersecurity", "Web Development",
    "Mobile Computing", "Parallel Computing", "Cloud Computing", "Calc "
]

# --- Data Cleanup (Remove existing records) ---
# These DELETE statements clear out existing data from the tables before inserting new data.
# This ensures a clean slate for each run of the script.
cursor.execute("DELETE FROM office_hours")
cursor.execute("DELETE FROM class_schedules")
cursor.execute("DELETE FROM professor_subjects")
cursor.execute("DELETE FROM group_subjects")
cursor.execute("DELETE FROM groups")
cursor.execute("DELETE FROM professors")
cursor.execute("DELETE FROM subjects")
cursor.execute("DELETE FROM careers")
cursor.execute("DELETE FROM classrooms")
cursor.execute("DELETE FROM users")

# --- User Data Generation (Admin and Student) ---

# Generate 2 administrator users.
for i in range(2):
    username = f"admin{i}"  # Constructs a unique username (e.g., admin0, admin1).
    name = fake.name()  # Generates a random full name using Faker.
    password = fake.password(length=10)  # Generates a random 10-character password.
    # Hashes the generated password using bcrypt for security.
    # The password is first encoded to UTF-8, then hashed, and finally decoded back to UTF-8.
    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    rol = "admin"  # Assigns the 'admin' role.
    # Inserts the user data into the 'users' table.
    cursor.execute("INSERT INTO users (username, password, rol, full_name, created_at) VALUES (%s, %s, %s, %s, %s)",
                   (username, hashed_password, rol, name, datetime.now()))

# Generate 30 student users.
for i in range(30):
    username = f"alumno{i}"  # Constructs a unique username (e.g., alumno0, alumno1, ...).
    name = fake.name()  # Generates a random full name.
    password = fake.password(length=10)  # Generates a random 10-character password.
    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')  # Hashes the password.
    rol = "student"  # Assigns the 'student' role.
    # Inserts the user data into the 'users' table.
    cursor.execute("INSERT INTO users (username, password, rol, full_name, created_at) VALUES (%s, %s, %s, %s, %s)",
                   (username, hashed_password, rol, name, datetime.now()))

# --- Careers Data Generation ---
career_ids = []  # List to store the auto-generated IDs of inserted careers.
# Iterate through the predefined CARRERAS list.
for nombre, _ in CARRERAS:  # Unpack each tuple, only using the 'nombre' (name).
    # Insert the career name into the 'careers' table.
    cursor.execute("INSERT INTO careers (nombre) VALUES (%s)", (nombre,))
    # Append the ID of the newly inserted row to the career_ids list.
    career_ids.append(cursor.lastrowid)

# --- Subjects Data Generation ---
subject_ids = []  # List to store the auto-generated IDs of inserted subjects.
used_subjects = set()  # Set to keep track of subjects already used to ensure uniqueness.
# Generate subjects based on the number of careers and subjects per career.
for _ in range(len(CARRERAS) * MATERIAS_POR_CARRERA):
    subject = random.choice(ENGINEERING_SUBJECTS)  # Randomly pick a subject from the predefined list.
    # Loop until a unique subject is chosen.
    while subject in used_subjects:
        subject = random.choice(ENGINEERING_SUBJECTS)
    used_subjects.add(subject)  # Add the chosen unique subject to the set of used subjects.
    # Insert the subject name into the 'subjects' table.
    cursor.execute("INSERT INTO subjects (nombre) VALUES (%s)", (subject,))
    # Append the ID of the newly inserted row to the subject_ids list.
    subject_ids.append(cursor.lastrowid)

# --- Classrooms Data Generation ---
classroom_ids = []  # List to store the auto-generated IDs of inserted classrooms.
salones_usados = set()  # Set to keep track of classroom names already used to ensure uniqueness.
# Generate 60 classroom names.
for _ in range(60):
    # Generate a 4-digit classroom name (e.g., '1207', '2019').
    salon = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    # Loop until a unique classroom name is generated.
    while salon in salones_usados:
        salon = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    salones_usados.add(salon)  # Add the unique classroom name to the set of used names.
    # Insert the classroom name into the 'classrooms' table.
    cursor.execute("INSERT INTO classrooms (nombre) VALUES (%s)", (salon,))
    # Append the ID of the newly inserted row to the classroom_ids list.
    classroom_ids.append(cursor.lastrowid)

# --- Professors and Their Subjects Data Generation ---
professor_ids = []  # List to store the auto-generated IDs of inserted professors.
oficinas_usadas = set()  # Set to keep track of office numbers already used to ensure uniqueness.
# Generate professors based on the number of careers and professors per career.
for _ in range(len(CARRERAS) * PROFESORES_POR_CARRERA):
    nombre = fake.name()  # Generates a random full name for the professor.
    email = fake.email()  # Generates a random email address.
    # Generate a 4-digit office number.
    oficina = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    # Loop until a unique office number is generated (not already used as a classroom or another office).
    while oficina in salones_usados or oficina in oficinas_usadas:
        oficina = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    oficinas_usadas.add(oficina)  # Add the unique office number to the set of used office numbers.
    # Insert professor details into the 'professors' table.
    cursor.execute("INSERT INTO professors (nombre_completo, email, oficina, state) VALUES (%s, %s, %s, %s)",
                   (nombre, email, oficina, "active"))
    prof_id = cursor.lastrowid  # Get the ID of the newly inserted professor.
    professor_ids.append(prof_id)  # Add the professor ID to the list.
    # Assign 1 to 3 random subjects to each professor.
    for _ in range(random.randint(1, 3)):
        subject_id = random.choice(subject_ids)  # Choose a random subject from the generated subjects.
        # Insert the association between the professor and subject into 'professor_subjects'.
        cursor.execute("INSERT INTO professor_subjects (id_professor, id_subject) VALUES (%s, %s)", (prof_id, subject_id))

# --- Groups and Subjects per Group Data Generation ---
group_ids = []  # List to store the auto-generated IDs of inserted groups.
group_subject_ids = []  # List to store the auto-generated IDs of inserted group_subjects associations.
# Iterate through each career to create groups specific to that career.
for idx, (nombre_carrera, sigla) in enumerate(CARRERAS):
    # Create a specified number of groups for the current career.
    for i in range(GRUPOS_POR_CARRERA):
        semestre = random.randint(1, 9)  # Assign a random semester (1-9).
        turno = random.choice(['M', 'V'])  # Assign a random shift ('M' for Morning, 'V' for Evening).
        # Construct a group name (e.g., "5C M3" for 5th semester, Career C, Morning shift, group 3).
        grupo = f"{semestre}{sigla}{turno}{random.randint(1,5)}"
        # Insert the group name and associated career ID into the 'groups' table.
        cursor.execute("INSERT INTO groups (nombre, id_career) VALUES (%s, %s)", (grupo, career_ids[idx]))
        group_id = cursor.lastrowid  # Get the ID of the newly inserted group.
        group_ids.append(group_id)  # Add the group ID to the list.
        # Assign 4 random subjects to each group.
        for _ in range(4):
            subject_id = random.choice(subject_ids)  # Choose a random subject.
            # Insert the association between the group and subject into 'group_subjects'.
            cursor.execute("INSERT INTO group_subjects (id_group, id_subject) VALUES (%s, %s)", (group_id, subject_id))
            group_subject_ids.append(cursor.lastrowid)  # Add the group_subject association ID to the list.

# --- Class Schedules Data Generation ---
# Generate 200 class schedules.
for _ in range(200):
    prof_id = random.choice(professor_ids)  # Randomly select a professor.
    group_subject_id = random.choice(group_subject_ids)  # Randomly select a group_subject association.
    dia = random.choice(DIAS)  # Randomly select a day of the week.
    hora_inicio, hora_fin = random.choice(HORAS)  # Randomly select a time slot.
    id_classroom = random.choice(classroom_ids)  # Randomly select a classroom.
    # Insert the class schedule details into the 'class_schedules' table.
    cursor.execute("""
        INSERT INTO class_schedules (id_professor, id_group_subject, dia, hora_inicio, hora_fin, id_classroom)
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (prof_id, group_subject_id, dia, hora_inicio, hora_fin, id_classroom))

# --- Office Hours Data Generation ---
# For each professor, generate 1 or 2 office hour slots.
for prof_id in professor_ids:
    for _ in range(random.randint(1, 2)):
        dia = random.choice(DIAS)  # Randomly select a day for office hours.
        hora_inicio, hora_fin = random.choice(HORAS)  # Randomly select a time slot for office hours.
        lugar = f"Oficina {random.randint(10, 99)}"  # Generate a generic office location.
        # Insert the office hour details into the 'office_hours' table.
        cursor.execute("""
            INSERT INTO office_hours (id_professor, dia, hora_inicio, hora_fin, lugar)
            VALUES (%s, %s, %s, %s, %s)
        """, (prof_id, dia, hora_inicio, hora_fin, lugar))

# --- Database Commit and Close ---
conn.commit()  # Commit all the pending changes to the database.
cursor.close()  # Close the cursor object.
conn.close()  # Close the database connection.

# Success message indicating data generation is complete.
"[+] Sucessfully -> Data Faker created!"
