(function(){
    const role = window.USER_ROLE || 'guest';
    const basePath = window.BASE_PATH || '';

    async function fetchTeacher() {
        try {
            const res = await fetch(basePath + '/api/teacher_stats.php');
            if (!res.ok) return;
            const data = await res.json();
            if (data.today_classes !== undefined) {
                const el = document.getElementById('today-classes-count');
                if (el) el.textContent = data.today_classes;
            }
            if (data.weekly_hours !== undefined) {
                const el = document.getElementById('weekly-hours-count');
                if (el) el.textContent = data.weekly_hours;
            }
            if (data.classes_taught !== undefined) {
                const el = document.getElementById('classes-taught-count');
                if (el) el.textContent = data.classes_taught;
            }
            if (data.next_class !== undefined) {
                const el = document.getElementById('next-class-text');
                if (el) el.textContent = data.next_class === 'None' ? 'None' : data.next_class;
            }
        } catch (err) {
            console.error('teacher stats error', err);
        }
    }

    async function fetchClassRep() {
        try {
            const res = await fetch(basePath + '/api/classrep_stats.php');
            if (!res.ok) return;
            const data = await res.json();
            if (data.today_scans !== undefined) {
                const el = document.getElementById('today-scans-count');
                if (el) el.textContent = data.today_scans;
            }
            if (data.weekly_scans !== undefined) {
                const el = document.getElementById('weekly-scans-count');
                if (el) el.textContent = data.weekly_scans;
            }
            if (data.today_teachers !== undefined) {
                const el = document.getElementById('today-teachers-count');
                if (el) el.textContent = data.today_teachers;
            }
            if (data.pending_scans !== undefined) {
                const el = document.getElementById('pending-scans-count');
                if (el) el.textContent = data.pending_scans;
            }
        } catch (err) {
            console.error('classrep stats error', err);
        }
    }

    // run once on load
    if (role === 'teacher') fetchTeacher();
    if (role === 'class_rep') fetchClassRep();

    // optional: poll every 60s
    setInterval(() => {
        if (role === 'teacher') fetchTeacher();
        if (role === 'class_rep') fetchClassRep();
    }, 60000);
})();
