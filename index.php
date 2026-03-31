<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Evaluation Portal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f4f9; }
        .container { max-width: 500px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 20px; }
        button { background: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #003d82; }
    </style>
</head>
<body>

<div class="container">
    <h2>Upload Monthly Data</h2>
    <form action="process.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv_file">Select Google Forms CSV:</label><br><br>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
        </div>
        <button type="submit">Process Data</button>
    </form>
</div>

</body>
</html>