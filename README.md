# Google Classroom Login PHP Script

This PHP application allows users to log in using Google OAuth and access their Google Classroom data.

## Setup

1. **Install Dependencies:**
   Run `composer install` to install the required PHP packages.

2. **Google API Setup:**
   - Go to the [Google Cloud Console](https://console.cloud.google.com/).
   - Create a new project or select an existing one.
   - Enable the Google Classroom API.
   - Create OAuth 2.0 credentials (Client ID and Client Secret).
   - Set the redirect URI to `http://localhost/oauth_callback.php` (adjust for your domain).

3. **Environment Variables:**
   - Copy the `.env` file and fill in your actual Google API credentials:
     - `GOOGLE_CLIENT_ID`: Your Google Client ID
     - `GOOGLE_CLIENT_SECRET`: Your Google Client Secret
     - `GOOGLE_REDIRECT_URI`: The redirect URI (e.g., http://localhost:8000/oauth_callback.php)

4. **Genrate the groups file:**
   - Run `php userList.php` to export the users to a CSV file.
   - The CSV file will be downloaded as `user_list.csv`.
   - The CSV file will have the following columns:
     - email
     - courseTitle
     - courseId
     - userId
   - Add a new column with the subgroup name.
   - Remove the email and courseTitle columns.
   - Save the file as `user_list.csv` in folder `resources` as `groups.csv`.

5. **Generate the roles file:**
   - The CSV file should have the following columns:
     - email
     - role
   - Save the file as `roles.csv` in folder `resources`.

5. **Run the Application:**
   - Start a PHP server: `php -S localhost:8000`
   - Open `http://localhost:8000/index.php` in your browser.

## Features

- Google OAuth login
- Responsive design with light blue and white theme
- Display user's Google Classroom courses
- Session-based authentication
- Roles and Subgroups management based on CSV files. [TODO] Option to extend two classes to fetch information from a database.

## Security Notes

- Never commit the `.env` file to version control.
- Use HTTPS in production.
- Regularly rotate your API keys.
- [TODO] move the .env file outside the webroot (use a changeable path in the source code) or disable access to the .env file.
- Don't allow the web server to serve content from the resources folder.
- Remove userList.php from the webroot after using it to generate the list.
