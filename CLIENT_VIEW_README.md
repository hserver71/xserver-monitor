# Client View Page - XServer Monitor

## Overview
The client view page has been completely redesigned to show clients from the database and implement the fetch servers functionality as requested. **Each client now fetches servers from their own domain** rather than from a single external API.

## Features Implemented

### 1. **Client List from Database**
- Shows all clients from the database in the left column
- Displays client name, IP address, and domain
- Each client has edit and delete buttons
- Active client selection with visual feedback

### 2. **Fetch Servers Functionality**
- **Individual Client Fetch**: Fetches servers from `client-domain/block_actions.php?action=server`
- **Global Fetch All**: Fetches servers from all clients sequentially
- **Smart Database Management**: 
  - Creates new servers
  - Updates existing servers
  - Removes servers that no longer exist in the source
  - Removes duplicate servers (keeps latest)
- **Domain-based API Integration**: Each client connects to their own domain

### 3. **Client Details & Servers Display**
- **Left Panel**: Client list with selection
- **Right Panel**: 
  - Client details when selected
  - Client's servers table
  - Server count badge
  - Refresh servers button

### 4. **Interactive Features**
- Click on any client to view details and servers
- Active client highlighting
- Loading states and spinners
- Success/error messages with detailed statistics
- Server management (edit/delete)

## API Endpoints

### 1. **Get Client Servers**
```
GET /api/clients/{client}/servers
```
Returns all servers for a specific client.

### 2. **Fetch Servers from Client Domain**
```
POST /api/fetch-servers
```
Body: `{"client_id": 1}`
Fetches servers from the client's own domain and updates database.

## How It Works

### 1. **Client Selection**
- Click on any client in the left panel
- Client details appear in the right panel
- Client's servers are automatically loaded
- Visual feedback shows active selection

### 2. **Fetching Servers from Client Domain**
- **Individual Fetch**: Click "Fetch Servers" button (requires client selection)
- **Global Fetch**: Click "Fetch All Clients" button to process all clients
- Makes HTTP request to `client-domain/block_actions.php?action=server`
- Processes response and updates database
- Shows loading modal with progress
- Displays detailed success/error messages

### 3. **Database Management**
- **New Servers**: Creates servers that don't exist
- **Updated Servers**: Updates existing servers with new data
- **Removed Servers**: Deletes servers that no longer exist in the source
- **Duplicate Removal**: Removes duplicate servers, keeping the latest version
- **Transaction Safety**: Uses database transactions for data integrity

### 4. **Server Management**
- View all servers for selected client
- Edit server details
- Delete servers
- Real-time updates after fetch operations

## Client Domain API Integration

Each client connects to their own domain:
```
http://client-domain.com/block_actions.php?action=server
https://client-domain.com/block_actions.php?action=server
```

**Expected Response Format:**
```json
[
    {
        "name": "Server Name",
        "ip": "192.168.1.100",
        "domain": "example.com"
    }
]
```

**Features:**
- 30-second timeout per client
- Automatic protocol detection (http/https)
- Sequential processing for multiple clients
- Error handling per client
- Comprehensive logging

## Database Structure

### Clients Table
- `id` - Primary key
- `name` - Client name
- `ip` - Client IP address
- `domain` - Client domain (used for API calls)
- `created_at`, `updated_at` - Timestamps

### Servers Table
- `id` - Primary key
- `client_id` - Foreign key to clients
- `name` - Server name
- `ip` - Server IP address
- `domain` - Server domain (optional)
- `created_at`, `updated_at` - Timestamps

## Usage Instructions

### 1. **View Clients**
- Navigate to `/clients`
- All clients are displayed from database
- Click on any client to select

### 2. **Fetch Servers for Individual Client**
- Select a client from the list
- Click "Fetch Servers" button
- Wait for API response from client's domain
- View updated server list

### 3. **Fetch Servers for All Clients**
- Click "Fetch All Clients" button
- System processes each client sequentially
- Progress is shown in loading modal
- Summary of results displayed

### 4. **Manage Servers**
- Select a client to view their servers
- Use edit/delete buttons for server management
- Refresh servers from client domain as needed

### 5. **Add New Client**
- Click "New Client" button
- Fill in client details (including domain)
- Submit form

## Fetch Process Details

### **Individual Client Fetch:**
1. Validates client has domain configured
2. Builds API URL: `client-domain/block_actions.php?action=server`
3. Makes HTTP request with 30-second timeout
4. Processes response and updates database
5. Removes servers no longer in source
6. Removes duplicate servers
7. Returns detailed statistics

### **Global Fetch All Clients:**
1. Gets all client IDs from current view
2. Processes clients sequentially (1-second delay between requests)
3. Shows progress for each client
4. Aggregates results and shows summary
5. Handles errors per client without stopping the process

## Error Handling

- **Missing Domain**: Error if client has no domain configured
- **API Failures**: Graceful handling with detailed error messages
- **Network Issues**: Timeout handling and retry logic
- **Invalid Data**: Validation and error logging
- **Database Errors**: Transaction rollback and user feedback
- **Partial Failures**: Continues processing other clients if one fails

## Development Notes

- CSRF protection enabled
- Comprehensive logging for all operations
- Database transactions for data integrity
- Sequential processing to avoid overwhelming systems
- Responsive design with Bootstrap 5
- FontAwesome icons for UI elements
- Progress tracking for bulk operations

## Future Enhancements

- Batch server operations
- Server status monitoring
- Performance metrics
- Advanced filtering and search
- Real-time updates via WebSockets
- Scheduled automatic fetching
- API rate limiting and throttling
- Server health checks 

