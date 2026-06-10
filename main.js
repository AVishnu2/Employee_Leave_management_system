/**
 * Client-side script for Employee Leave Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Sidebar Toggler for Mobile Layouts
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('show');
        });
    }

    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('show');
        });
    }

    // 2. Auto-calculation of Leave Days
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const calculatedDaysDiv = document.getElementById('calculated_days_container');
    const leaveDaysSpan = document.getElementById('calculated_days');

    function calculateLeaveDays() {
        if (!startDateInput || !endDateInput || !leaveDaysSpan) return;

        const startVal = startDateInput.value;
        const endVal = endDateInput.value;

        if (startVal && endVal) {
            const start = new Date(startVal);
            const end = new Date(endVal);

            // Clean time boundaries
            start.setHours(0,0,0,0);
            end.setHours(0,0,0,0);

            if (end < start) {
                if (calculatedDaysDiv) {
                    calculatedDaysDiv.classList.remove('alert-info');
                    calculatedDaysDiv.classList.add('alert-danger');
                    calculatedDaysDiv.style.display = 'block';
                }
                leaveDaysSpan.innerText = 'Error: End Date cannot be before Start Date';
                endDateInput.classList.add('is-invalid');
            } else {
                endDateInput.classList.remove('is-invalid');
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Inclusive

                if (calculatedDaysDiv) {
                    calculatedDaysDiv.classList.remove('alert-danger');
                    calculatedDaysDiv.classList.add('alert-info');
                    calculatedDaysDiv.style.display = 'block';
                }
                leaveDaysSpan.innerText = diffDays + (diffDays === 1 ? ' Day' : ' Days');
            }
        } else {
            if (calculatedDaysDiv) {
                calculatedDaysDiv.style.display = 'none';
            }
        }
    }

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', calculateLeaveDays);
        endDateInput.addEventListener('change', calculateLeaveDays);
    }

    // 3. Set Leave ID dynamically in Admin Modals
    const actionModals = document.querySelectorAll('.modal-action-trigger');
    actionModals.forEach(button => {
        button.addEventListener('click', function() {
            const leaveId = this.getAttribute('data-leave-id');
            const action = this.getAttribute('data-action'); // 'Approve' or 'Reject'
            const employeeName = this.getAttribute('data-employee-name');
            const leaveType = this.getAttribute('data-leave-type');

            // Find matching inputs in the targeted action modal
            const inputLeaveId = document.getElementById('action_leave_id');
            const inputAction = document.getElementById('action_type');
            
            // Set dynamic header text in modal
            const labelEmployee = document.getElementById('modalEmployeeName');
            const labelLeaveType = document.getElementById('modalLeaveType');
            const labelActionTitle = document.getElementById('modalActionTitle');

            if (inputLeaveId) inputLeaveId.value = leaveId;
            if (inputAction) inputAction.value = action;
            if (labelEmployee) labelEmployee.innerText = employeeName;
            if (labelLeaveType) labelLeaveType.innerText = leaveType;
            
            if (labelActionTitle) {
                labelActionTitle.innerText = action;
                if (action === 'Approve') {
                    labelActionTitle.className = 'text-success fw-bold';
                } else {
                    labelActionTitle.className = 'text-danger fw-bold';
                }
            }
        });
    });

    // 4. Auto-fade out message alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.6s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 600);
        }, 5000);
    });
});
