<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payslip - {{ $payroll->user?->name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            direction: ltr;
            text-align: left;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #64748b;
        }
        .info-table, .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 8px;
            vertical-align: top;
        }
        .financial-table th, .financial-table td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: left;
        }
        .financial-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 12px;
        }
        .text-right {
            text-align: right;
        }
        .net-salary {
            background-color: #dbeafe;
            font-weight: bold;
            font-size: 15px;
            color: #1e40af;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Salary Payslip</h2>
        <p>For Month: {{ $payroll->month_year }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Employee Name:</strong> {{ $payroll->user?->name }}</td>
            <td><strong>Employee ID:</strong> {{ $payroll->user?->employee_id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Job Title:</strong> {{ $payroll->user?->job_title ?? 'N/A' }}</td>
            <td><strong>Department:</strong> {{ $payroll->user?->department?->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <table class="financial-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th class="text-right">Amount ($)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td class="text-right">{{ number_format($payroll->basic_salary, 2) }}</td>
            </tr>
            <tr>
                <td>Total Bonuses & Incentives</td>
                <td class="text-right">+ {{ number_format($payroll->total_bonuses, 2) }}</td>
            </tr>
            <tr>
                <td>Total Deductions</td>
                <td class="text-right">- {{ number_format($payroll->total_deductions, 2) }}</td>
            </tr>
            <tr>
                <td>Monthly Loan Installment</td>
                <td class="text-right">- {{ number_format($payroll->loan_installment, 2) }}</td>
            </tr>
            <tr class="net-salary">
                <td>Net Payable Salary</td>
                <td class="text-right">{{ number_format($payroll->net_salary, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated payslip from the HR Management System and requires no signature.</p>
    </div>

</body>
</html>