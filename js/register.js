        function setRole(role) {
            const roleInput = document.getElementById('roleInput');
            const btnStudent = document.getElementById('btnStudent');
            const btnTeacher = document.getElementById('btnTeacher');
            const userCodeInput = document.getElementById('userCodeInput');

            roleInput.value = role;

            if (role === 'student') {
                btnStudent.classList.add('active');
                btnTeacher.classList.remove('active');
                userCodeInput.placeholder = userCodeInput.dataset.studentPlaceholder;
            } else {
                btnTeacher.classList.add('active');
                btnStudent.classList.remove('active');
                userCodeInput.placeholder = userCodeInput.dataset.teacherPlaceholder;
            }
        }
    