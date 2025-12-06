<!--
  Requirement: Create a Responsive Admin Portal

  Instructions:
  Fill in the HTML elements as described in the comments.
  Use the provided IDs for the elements that require them.
  Focus on creating a clear and semantic HTML structure.
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- TODO: Add the 'meta' tag for character encoding (UTF-8). -->
    <meta charset="UTF-8" />

    <!-- TODO: Add the responsive 'viewport' meta tag. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- TODO: Add a 'title' for the page, e.g., "Admin Portal". -->
    <title>Admin Portal</title>

    <!-- TODO: Link to a CSS file or a CSS framework. -->
    <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@1.*/css/pico.min.css">
</head>
<body>

    <!-- TODO: Create a 'header' element for the top of the page. -->
        <!-- TODO: Inside the header, add a main heading (e.g., 'h1') with the text "Admin Portal". -->
        <header>
            <h1>Admin Portal</h1>
        </header>
    <!-- End of the header. -->

    <!-- TODO: Create a 'main' element to hold the primary content of the portal. -->
    <main>

        <!-- Section 1: Password Management -->
        <!-- TODO: Create a 'section' for the password management functionality. -->
        <section id="password-management">
            <!-- TODO: Add a sub-heading (e.g., 'h2') with the text "Change Your Password". -->
            <h2>Change Your Password</h2>

            <!-- TODO: Create a 'form' for changing the password. The 'action' can be '#'. -->
            <form action="#" method="post">
                <!-- TODO: Use a 'fieldset' to group the password-related fields. -->
                <fieldset>
                    <!-- TODO: Add a 'legend' for the fieldset, e.g., "Password Update". -->
                    <legend>Password Update</legend>

                    <!-- TODO: Add a 'label' for the current password input. 'for' should be "current-password". -->
                    <label for="current-password">Current Password:</label>
                    <!-- TODO: Add an 'input' for the current password. -->
                    <input type="password" id="current-password" required>

                    <!-- TODO: Add a 'label' for the new password input. 'for' should be "new-password". -->
                    <label for="new-password">New Password:</label>
                    <!-- TODO: Add an 'input' for the new password. -->
                    <input type="password" id="new-password" minlength="8" required>

                    <!-- TODO: Add a 'label' for the confirm password input. 'for' should be "confirm-password". -->
                    <label for="confirm-password">Confirm New Password:</label>
                    <!-- TODO: Add an 'input' to confirm the new password. -->
                    <input type="password" id="confirm-password" required>

                    <!-- TODO: Add a 'button' to submit the form. -->
                    <button type="submit" id="change">Update Password</button>

                <!-- End of the fieldset. -->
                </fieldset>
            <!-- End of the password form. -->
            </form>
        </section>
        <!-- End of the password management section. -->


        <!-- Section 2: Student Management -->
        <!-- TODO: Create another 'section' for the student management functionality. -->
        <section id="student-management">
            <!-- TODO: Add a sub-heading (e.g., 'h2') with the text "Manage Students". -->
            <h2>Manage Students</h2>

            <!-- Subsection 2.1: Add New Student Form -->
            <!-- TODO: Create a 'details' element so the "Add Student" form can be collapsed. -->
            <details>
                <!-- TODO: Add a 'summary' element inside 'details' with the text "Add New Student". -->
                <summary>Add New Student</summary>

                <!-- TODO: Create a 'form' for adding a new student. 'action' can be '#'. -->
                <form action="#" method="post">
                    <!-- TODO: Use a 'fieldset' to group the new student fields. -->
                    <fieldset>
                        <!-- TODO: Add a 'legend' for the fieldset, e.g., "New Student Information". -->
                        <legend>New Student Information</legend>

                        <!-- TODO: Add a 'label' and 'input' for the student's full name. -->
                        <label for="student-name">Full Name:</label>
                        <input type="text" id="student-name" required>

                        <!-- TODO: Add a 'label' and 'input' for the student's ID. -->
                        <label for="student-id">Student ID:</label>
                        <input type="text" id="student-id" required>

                        <!-- TODO: Add a 'label' and 'input' for the student's email. -->
                        <label for="student-email">Email:</label>
                        <input type="email" id="student-email" required>

                        <!-- TODO: Add a 'label' and 'input' for the default password. -->
                        <label for="default-password">Default Password:</label>
                        <input type="text" id="default-password" value="password123">

                        <!-- TODO: Add a 'button' to submit the form. -->
                        <button type="submit" id="add">Add Student</button>

                    <!-- End of the fieldset. -->
                    </fieldset>
                <!-- End of the add student form. -->
                </form>
            <!-- End of the 'details' element. -->
            </details>


            <!-- Subsection 2.2: Student List -->
            <!-- TODO: Add a sub-heading (e.g., 'h3') for the list of students, "Registered Students". -->
            <h3>Registered Students</h3>

            <!-- TODO: Create a 'table' to display the list of students. Give it an id="student-table". -->
            <table id="student-table">
                <!-- TODO: Create a 'thead' for the table headers. -->
                <thead>
                    <!-- TODO: Create a 'tr' (table row) inside the 'thead'. -->
                    <tr>
                        <!-- TODO: Create 'th' (table header) cells for "Name", "Student ID", "Email", and "Actions". -->
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                    <!-- End of the row. -->
                </thead>
                <!-- End of 'thead'. -->

                <!-- TODO: Create a 'tbody' for the table body, where student data will go. -->
                <tbody>
                    <!-- TODO: For now, add 2-3 rows of dummy data so you can see how the table is structured. -->
                    <tr>
                        <td>John Doe</td>
                        <td>12345</td>
                        <td>john.doe@example.com</td>
                        <td>
                            <!-- TODO: Add an "Edit" button. -->
                            <button>Edit</button>
                            <!-- TODO: Add a "Delete" button. -->
                            <button>Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Jane Smith</td>
                        <td>67890</td>
                        <td>jane.smith@example.com</td>
                        <td>
                            <button>Edit</button>
                            <button>Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Michael Lee</td>
                        <td>54321</td>
                        <td>michael.lee@example.com</td>
                        <td>
                            <button>Edit</button>
                            <button>Delete</button>
                        </td>
                    </tr>
                </tbody>
                <!-- End of 'tbody'. -->
            </table>
            <!-- End of the table. -->

        <!-- End of the student management section. -->
        </section>

    <!-- End of the main content area. -->
    </main>

</body>
</html>
