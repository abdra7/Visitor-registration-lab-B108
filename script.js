// بيانات الدخول (بسيطة بدون تشفير)
const ADMIN_USER = "admin";
const ADMIN_PASS = "12345";

// التحقق من تسجيل الدخول
function loginAdmin(event) {
  event.preventDefault();
  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;
  const errorMsg = document.getElementById("login-error");

  if (username === ADMIN_USER && password === ADMIN_PASS) {
    document.getElementById("loader").style.display = "block"; // عرض اللوقو
    setTimeout(() => {
      window.location.href = "dashboard.html"; // بعد 2 ثانية ننتقل
    }, 2000);
  } else {
    errorMsg.textContent = "اسم المستخدم أو كلمة المرور غير صحيحة!";
  }
}

// تسجيل الطلاب
let students = [];

function addStudent(event) {
  event.preventDefault();
  const name = document.getElementById("student-name").value;
  const id = document.getElementById("student-id").value;
  const timeIn = new Date().toLocaleTimeString();

  const student = {
    name,
    id,
    timeIn,
    timeOut: ""
  };

  students.push(student);
  updateTable();
  document.getElementById("student-form").reset();
}

function updateTable() {
  const table = document.getElementById("students-table");
  table.innerHTML = "";

  students.forEach((student, index) => {
    const row = document.createElement("tr");

    row.innerHTML = `
      <td>${student.name}</td>
      <td>${student.id}</td>
      <td>${student.timeIn}</td>
      <td>${student.timeOut || "-"}</td>
      <td>
        <button onclick="checkOut(${index})" ${student.timeOut ? "disabled" : ""}>تسجيل خروج</button>
      </td>
    `;

    table.appendChild(row);
  });
}

function checkOut(index) {
  students[index].timeOut = new Date().toLocaleTimeString();
  updateTable();
}
