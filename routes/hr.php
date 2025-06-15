<?php

use Livewire\Volt\Volt;

// HR Dashboard
Volt::route('/hr', 'hr.dashboard')->name('hr.dashboard');

// Employee Management
Volt::route('/hr/employees', 'hr.employees.index')->name('hr.employees.index');
Volt::route('/hr/employees/create', 'hr.employees.create')->name('hr.employees.create');
Volt::route('/hr/employees/{employee}', 'hr.employees.show')->name('hr.employees.show');
Volt::route('/hr/employees/{employee}/edit', 'hr.employees.edit')->name('hr.employees.edit');

// Performance Reviews
Volt::route('/hr/performance', 'hr.performance.index')->name('hr.performance.index');
Volt::route('/hr/performance/create', 'hr.performance.create')->name('hr.performance.create');
Volt::route('/hr/performance/{review}', 'hr.performance.show')->name('hr.performance.show');
Volt::route('/hr/performance/{review}/edit', 'hr.performance.edit')->name('hr.performance.edit');

// Leave Management
Volt::route('/hr/leaves', 'hr.leaves.index')->name('hr.leaves.index');
Volt::route('/hr/leaves/create', 'hr.leaves.create')->name('hr.leaves.create');
Volt::route('/hr/leaves/{leave}', 'hr.leaves.show')->name('hr.leaves.show');

// Training Management
Volt::route('/hr/trainings', 'hr.trainings.index')->name('hr.trainings.index');
Volt::route('/hr/trainings/create', 'hr.trainings.create')->name('hr.trainings.create');
Volt::route('/hr/trainings/assign', 'hr.trainings.assign')->name('hr.trainings.assign');
Volt::route('/hr/trainings/{training}', 'hr.trainings.show')->name('hr.trainings.show');

// OKR Management
Volt::route('/hr/okr', 'hr.okr.index')->name('hr.okr.index');
Volt::route('/hr/okr/create', 'hr.okr.create')->name('hr.okr.create');
Volt::route('/hr/okr/{goal}', 'hr.okr.show')->name('hr.okr.show');
Volt::route('/hr/okr/{goal}/edit', 'hr.okr.edit')->name('hr.okr.edit');

// Certification Management
Volt::route('/hr/certifications', 'hr.certifications.index')->name('hr.certifications.index');
Volt::route('/hr/certifications/create', 'hr.certifications.create')->name('hr.certifications.create');
Volt::route('/hr/certifications/{certification}', 'hr.certifications.show')->name('hr.certifications.show');
Volt::route('/hr/certifications/{certification}/edit', 'hr.certifications.edit')->name('hr.certifications.edit');

// Payroll
Volt::route('/hr/payroll', 'hr.payroll.index')->name('hr.payroll.index');
Volt::route('/hr/payroll/create', 'hr.payroll.create')->name('hr.payroll.create');
Volt::route('/hr/payroll/{payroll}', 'hr.payroll.show')->name('hr.payroll.show');

// Reports
Volt::route('/hr/reports', 'hr.reports.index')->name('hr.reports.index');
Volt::route('/hr/reports/performance', 'hr.reports.performance')->name('hr.reports.performance');
Volt::route('/hr/reports/attendance', 'hr.reports.attendance')->name('hr.reports.attendance');
Volt::route('/hr/reports/payroll', 'hr.reports.payroll')->name('hr.reports.payroll');
