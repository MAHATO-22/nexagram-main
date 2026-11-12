        function setRole(role) {
            const roleInput = document.getElementById('roleInput');
            const btnStudent = document.getElementById('btnStudent');
            const btnTeacher = document.getElementById('btnTeacher');
            const userCodeInput = document.getElementById('userCodeInput');
            const userCodeGroup = document.getElementById('userCodeGroup');
            const departmentGroup = document.getElementById('departmentGroup');
            const classGroup = document.getElementById('classGroup');
            const schoolGroup = document.getElementById('schoolGroup');
            const departmentInput = departmentGroup.querySelector('select');
            const classInput = classGroup.querySelector('input');
            const schoolInput = schoolGroup.querySelector('input');
            const isStudent = role === 'student';

            roleInput.value = role;
            btnStudent.classList.toggle('active', isStudent);
            btnTeacher.classList.toggle('active', !isStudent);

            if (isStudent) {
                userCodeInput.placeholder = userCodeInput.dataset.studentPlaceholder;
            } else {
                userCodeInput.placeholder = '';
            }

            userCodeGroup.hidden = !isStudent;
            departmentGroup.hidden = !isStudent;
            classGroup.hidden = !isStudent;
            schoolGroup.hidden = !isStudent;
            userCodeInput.disabled = !isStudent;
            departmentInput.disabled = !isStudent;
            classInput.disabled = !isStudent;
            schoolInput.disabled = !isStudent;
            userCodeInput.required = isStudent;
            departmentInput.required = isStudent;
        }
    