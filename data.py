import random
import mysql.connector
import bcrypt
from faker import Faker
from datetime import datetime

fake = Faker('es_MX')

conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",  
    database="escom_schedule"
)
cursor = conn.cursor()

CARRERAS = ['Ingeniería en Sistemas Computacionales', 'Ingeniería en Inteligencia Artificial', 'Licenciatura en Ciencia de Datos']
MATERIAS_POR_CARRERA = 10
PROFESORES_POR_CARRERA = 33
GRUPOS_POR_CARRERA = 15
DIAS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
HORAS = [('07:00:00', '08:30:00'), ('08:30:00', '10:00:00'), ('10:30:00', '12:00:00'), ('12:00:00', '13:30:00'), ('13:30:00', '15:00:00'), ('15:00:00', '16:30:00'), 
        ('16:30:00', '18:00:00'), ('18:30:00', '20:00:00'), ('20:00:00', '21:30:00')]

# admin
for i in range(2):
    username = f"admin{i}"
    name = fake.name()
    password = fake.password(length=10)
    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    rol = "admin"
    cursor.execute("INSERT INTO users (username, password, rol, full_name, created_at) VALUES (%s, %s, %s, %s, %s)", (username, hashed_password, rol, name, datetime.now()))
    
# 30 students
for i in range(30):
    username = f"alumno{i}"
    name  = fake.name()
    password = fake.password(length=10)
    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    rol = "student"
    cursor.execute("INSERT INTO users (username, password, rol, full_name, created_at) VALUES (%s, %s, %s, %s, %s)", (username, hashed_password, rol, name, datetime.now()))

career_ids = []
for nombre in CARRERAS:
    cursor.execute("INSERT INTO careers (nombre) VALUES (%s)", (nombre,))
    career_ids.append(cursor.lastrowid)

subject_ids = []
for _ in range(len(CARRERAS) * MATERIAS_POR_CARRERA):
    nombre_materia = fake.catch_phrase()
    cursor.execute("INSERT INTO subjects (nombre) VALUES (%s)", (nombre_materia,))
    subject_ids.append(cursor.lastrowid)

professor_ids = []
for i in range(len(CARRERAS) * PROFESORES_POR_CARRERA):
    nombre = fake.name()
    email = fake.email()
    oficina = f"A{random.randint(100, 599)}"
    cursor.execute("INSERT INTO professors (nombre_completo, email, oficina) VALUES (%s, %s, %s)", (nombre, email, oficina))
    prof_id = cursor.lastrowid
    professor_ids.append(prof_id)

group_ids = []
for i, career_id in enumerate(career_ids):
    for j in range(GRUPOS_POR_CARRERA):
        nombre = f"{CARRERAS[i][:3].upper()}{j+1}"
        cursor.execute("INSERT INTO groups (nombre, id_career) VALUES (%s, %s)", (nombre, career_id))
        group_ids.append(cursor.lastrowid)

group_subject_ids = []
for group_id in group_ids:
    for _ in range(4):
        subject_id = random.choice(subject_ids)
        cursor.execute("INSERT INTO group_subjects (id_group, id_subject) VALUES (%s, %s)", (group_id, subject_id))
        group_subject_ids.append(cursor.lastrowid)

for prof_id in professor_ids:
    for _ in range(random.randint(1, 3)):
        subject_id = random.choice(subject_ids)
        cursor.execute("INSERT INTO professor_subjects (id_professor, id_subject) VALUES (%s, %s)", (prof_id, subject_id))

for _ in range(200):
    prof_id = random.choice(professor_ids)
    group_subject_id = random.choice(group_subject_ids)
    dia = random.choice(DIAS)
    hora_inicio, hora_fin = random.choice(HORAS)
    aula = f"{random.choice(['A', 'B', 'C'])}{random.randint(1, 20)}"
    cursor.execute("""
        INSERT INTO class_schedules (id_professor, id_group_subject, dia, hora_inicio, hora_fin, aula)
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (prof_id, group_subject_id, dia, hora_inicio, hora_fin, aula))

for prof_id in professor_ids:
    for _ in range(random.randint(1, 2)):
        dia = random.choice(DIAS)
        hora_inicio, hora_fin = random.choice(HORAS)
        lugar = f"Oficina {random.randint(1, 100)}"
        cursor.execute("""
            INSERT INTO office_hours (id_professor, dia, hora_inicio, hora_fin, lugar)
            VALUES (%s, %s, %s, %s, %s)
        """, (prof_id, dia, hora_inicio, hora_fin, lugar))

conn.commit()
cursor.close()
conn.close()

print("Satifactory !")