# Debug Guide for Fetch Servers from Client Domain

## Overview
This guide helps you troubleshoot issues with the fetch servers functionality. The system now includes comprehensive logging that will help identify where problems occur.

## Log Locations
By default, Laravel logs are stored in:
```
storage/logs/laravel.log
```

## Key Debug Log Entries

### 1. **Start of Operation**
```
[INFO] === START: Fetch Servers from Client Domain ===
```
**Contains:**
- Request data
- Timestamp
- Memory usage at start
- Peak memory usage

### 2. **Client Information**
```
[INFO] Client found
```
**Contains:**
- Client ID, name, IP, domain
- Creation and update timestamps

### 3. **API URL Building**
```
[INFO] Building client API URL
[INFO] Final API URL built
```
**Contains:**
- Input domain
- Protocol detection
- Final constructed URL
- URL components breakdown

### 4. **Current Database State**
```
[INFO] Current servers in database
```
**Contains:**
- Current server count
- Current server IPs
- Full server details

### 5. **HTTP Request**
```
[INFO] Making HTTP request
[INFO] HTTP response received
```
**Contains:**
- Target URL
- Timeout settings
- Response status
- Response headers
- Response size
- Request time
- Response body preview (first 500 chars)

### 6. **API Response Processing**
```
[INFO] API response parsed
[INFO] API data validation passed
```
**Contains:**
- Data type validation
- Array count
- Data structure analysis
- IP format validation

### 7. **Database Operations**
```
[INFO] Processing API data
[INFO] Server created/updated
[INFO] Servers to be removed
[INFO] Duplicate removal completed
```
**Contains:**
- Processing progress
- Individual server operations
- Removal operations
- Duplicate handling

### 8. **Transaction Status**
```
[INFO] Starting database transaction
[INFO] Database transaction committed successfully
```
**Contains:**
- Transaction start/commit confirmation

### 9. **Operation Summary**
```
[INFO] === SUCCESS: Fetch Servers from Client Domain ===
```
**Contains:**
- Complete operation summary
- Before/after server counts
- Created/updated/removed counts
- Final server list
- Memory usage statistics

### 10. **Error Information**
```
[ERROR] === ERROR: Fetch Servers from Client Domain ===
```
**Contains:**
- Error message
- Error code
- File and line number
- Full stack trace
- Memory usage at error

## Common Issues and Debug Steps

### **Issue: Client has no domain**
**Look for:**
```
[ERROR] Client does not have a domain configured
```
**Solution:** Ensure the client record has a domain value in the database.

### **Issue: Invalid domain format**
**Look for:**
```
[INFO] Building client API URL
[INFO] Final API URL built
```
**Check:** The final URL should be properly formatted with protocol and path.

### **Issue: HTTP request fails**
**Look for:**
```
[INFO] Making HTTP request
[ERROR] Client API request failed: [STATUS_CODE]
```
**Check:**
- Domain is accessible
- `/block_actions.php?action=server` endpoint exists
- Server responds within 30 seconds

### **Issue: Invalid API response**
**Look for:**
```
[INFO] API response parsed
[ERROR] Invalid response format from client API
```
**Check:**
- API returns valid JSON array
- Each item has `name` and `ip` fields
- IP addresses are valid format

### **Issue: Database transaction fails**
**Look for:**
```
[INFO] Starting database transaction
[ERROR] Database transaction rolled back due to error
```
**Check:**
- Database connection
- Table structure
- Foreign key constraints
- Server permissions

### **Issue: Memory problems**
**Look for:**
```
[INFO] Memory usage: [VALUE]
[INFO] Peak memory: [VALUE]
```
**Check:** Memory usage should be reasonable (typically under 50MB for normal operations).

## Debug Commands

### **View Recent Logs**
```bash
tail -f storage/logs/laravel.log
```

### **Search for Specific Client**
```bash
grep "client_id: [ID]" storage/logs/laravel.log
```

### **Search for Errors**
```bash
grep "ERROR" storage/logs/laravel.log
```

### **Search for Specific Operation**
```bash
grep "Fetch Servers from Client Domain" storage/logs/laravel.log
```

## Testing the API Endpoint

### **Manual Test**
Test the client's API endpoint manually:
```bash
curl "http://client-domain.com/block_actions.php?action=server"
```

**Expected Response:**
```json
[
    {
        "name": "Server Name",
        "ip": "192.168.1.100",
        "domain": "example.com"
    }
]
```

### **Check Response Headers**
```bash
curl -I "http://client-domain.com/block_actions.php?action=server"
```

**Look for:**
- `Content-Type: application/json`
- `HTTP/1.1 200 OK`

## Performance Monitoring

### **Request Time**
Look for:
```
[INFO] request_time_seconds: [VALUE]
```
**Normal:** Under 5 seconds
**Warning:** 5-15 seconds
**Critical:** Over 15 seconds

### **Memory Usage**
Look for:
```
[INFO] memory_usage: [VALUE]
[INFO] peak_memory: [VALUE]
```
**Normal:** Under 50MB
**Warning:** 50-100MB
**Critical:** Over 100MB

## Troubleshooting Checklist

- [ ] Client has domain configured
- [ ] Domain is accessible from server
- [ ] API endpoint exists and responds
- [ ] API returns valid JSON
- [ ] Database connection works
- [ ] Server has sufficient memory
- [ ] No firewall/network issues
- [ ] Client API responds within timeout

## Getting Help

If you're still having issues after checking the logs:

1. **Copy the complete log section** from start to finish
2. **Note the specific error messages**
3. **Check the client's API endpoint manually**
4. **Verify database connectivity**
5. **Check server resources** (memory, disk space)

The detailed logging should provide enough information to identify the exact point of failure. 