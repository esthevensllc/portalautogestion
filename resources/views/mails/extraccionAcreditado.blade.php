<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plantilla de correo</title>
</head>
<body>
    <p>SE TIENE EL SIGUIENTE TICKET ACREDITADO:</p>
    <table border="1" cellspacing="0" cellpadding="0" width="500px">
        <thead>
            <tr style="background-color:#dc3545;color:white;margin:0;">
                <th>Ticket</th>
                <th>Departamento</th>
                <th>Modalidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0;border-bottom-width:1pt;">{{ $informeFallas->ticket }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $informeFallas->departamento }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $modalidad }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
