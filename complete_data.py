import random
import mysql.connector
import bcrypt
from faker import Faker
from datetime import datetime

fake = Faker('en_US')

conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",  
    database="escom_schedule"
)
cursor = conn.cursor()

# Configuraciones
CARRERAS = [
    ("Ingeniería en Sistemas Computacionales", "C"),
    ("Ingeniería en Inteligencia Artificial", "I"),
    ("Licenciatura en Ciencia de Datos", "L")
]
MATERIAS_POR_CARRERA = 10
PROFESORES_POR_CARRERA = 33
GRUPOS_POR_CARRERA = 15
DIAS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
HORAS = [
    ('07:00:00', '08:30:00'), ('08:30:00', '10:00:00'), ('10:30:00', '12:00:00'),
    ('12:00:00', '13:30:00'), ('13:30:00', '15:00:00'), ('15:00:00', '16:30:00'),
    ('16:30:00', '18:00:00'), ('18:30:00', '20:00:00'), ('20:00:00', '21:30:00')
]
ENGINEERING_SUBJECTS = [
    "Programming Fundamentals", "Data Structures", "Algorithms", "Operating Systems",
    "Computer Networks", "Database Systems", "Artificial Intelligence", "Machine Learning",
    "Computer Architecture", "Software Engineering", "Cybersecurity", "Web Development",
    "Mobile Computing", "Parallel Computing", "Cloud Computing", "Calc "
]

# Eliminar registros anteriores (opcional)
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

# Usuarios admin y estudiantes
for i in range(2):
    username = f"admin{i}"
    name = fake.name()
    password = fake.password(length=10)
    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    rol = "admin"
    cursor.execute("INSERT INTO users (username, password, rol, full_name, created_at) VALUES (%s, %s, %s, %s, %s)", 
                   (username, hashed_password, rol, name, datetime.now()))

for i in range(30):
    username = f"alumno{i}"
    name = fake.name()
    password = fake.password(length=10)
    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    rol = "student"
    cursor.execute("INSERT INTO users (username, password, rol, full_name, created_at) VALUES (%s, %s, %s, %s, %s)", 
                   (username, hashed_password, rol, name, datetime.now()))

# Carreras
career_ids = []
for nombre, _ in CARRERAS:
    cursor.execute("INSERT INTO careers (nombre) VALUES (%s)", (nombre,))
    career_ids.append(cursor.lastrowid)

# Materias
subject_ids = []
used_subjects = set()
for _ in range(len(CARRERAS) * MATERIAS_POR_CARRERA):
    subject = random.choice(ENGINEERING_SUBJECTS)
    while subject in used_subjects:
        subject = random.choice(ENGINEERING_SUBJECTS)
    used_subjects.add(subject)
    cursor.execute("INSERT INTO subjects (nombre) VALUES (%s)", (subject,))
    subject_ids.append(cursor.lastrowid)

# Salones (classrooms) con formato 1207, 2019...
classroom_ids = []
salones_usados = set()
for _ in range(60):
    salon = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    while salon in salones_usados:
        salon = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    salones_usados.add(salon)
    cursor.execute("INSERT INTO classrooms (nombre) VALUES (%s)", (salon,))
    classroom_ids.append(cursor.lastrowid)

# Profesores y sus materias
professor_ids = []
oficinas_usadas = set()
for _ in range(len(CARRERAS) * PROFESORES_POR_CARRERA):
    nombre = fake.name()
    email = fake.email()
    oficina = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    while oficina in salones_usados or oficina in oficinas_usadas:
        oficina = f"{random.randint(1,4)}{random.randint(0,9)}{random.randint(0,9)}{random.randint(0,9)}"
    oficinas_usadas.add(oficina)
    cursor.execute("INSERT INTO professors (nombre_completo, email, oficina, state) VALUES (%s, %s, %s, %s)", 
                   (nombre, email, oficina, "active"))
    prof_id = cursor.lastrowid
    professor_ids.append(prof_id)
    # Materias del profesor (1 a 3)
    for _ in range(random.randint(1,3)):
        subject_id = random.choice(subject_ids)
        cursor.execute("INSERT INTO professor_subjects (id_professor, id_subject) VALUES (%s, %s)", (prof_id, subject_id))

# Grupos y materias por grupo
group_ids = []
group_subject_ids = []
for idx, (nombre_carrera, sigla) in enumerate(CARRERAS):
    for i in range(GRUPOS_POR_CARRERA):
        semestre = random.randint(1, 9)
        turno = random.choice(['M', 'V'])
        grupo = f"{semestre}{sigla}{turno}{random.randint(1,5)}"
        cursor.execute("INSERT INTO groups (nombre, id_career) VALUES (%s, %s)", (grupo, career_ids[idx]))
        group_id = cursor.lastrowid
        group_ids.append(group_id)
        # Materias del grupo
        for _ in range(4):
            subject_id = random.choice(subject_ids)
            cursor.execute("INSERT INTO group_subjects (id_group, id_subject) VALUES (%s, %s)", (group_id, subject_id))
            group_subject_ids.append(cursor.lastrowid)

# Horarios de clase
for _ in range(200):
    prof_id = random.choice(professor_ids)
    group_subject_id = random.choice(group_subject_ids)
    dia = random.choice(DIAS)
    hora_inicio, hora_fin = random.choice(HORAS)
    id_classroom = random.choice(classroom_ids)
    cursor.execute("""
        INSERT INTO class_schedules (id_professor, id_group_subject, dia, hora_inicio, hora_fin, id_classroom)
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (prof_id, group_subject_id, dia, hora_inicio, hora_fin, id_classroom))

# Horarios de oficina
for prof_id in professor_ids:
    for _ in range(random.randint(1, 2)):
        dia = random.choice(DIAS)
        hora_inicio, hora_fin = random.choice(HORAS)
        lugar = f"Oficina {random.randint(10, 99)}"
        cursor.execute("""
            INSERT INTO office_hours (id_professor, dia, hora_inicio, hora_fin, lugar)
            VALUES (%s, %s, %s, %s, %s)
        """, (prof_id, dia, hora_inicio, hora_fin, lugar))

conn.commit()
cursor.close()
conn.close()

"Satisfactorio: datos generados con estructura académica personalizada."
