#!/bin/bash

# ==============================================================================
# QUICK VERIFICATION SCRIPT - Call System Status Check
# ==============================================================================

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║          CALL SYSTEM IMPLEMENTATION VERIFICATION              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Change to server directory
cd "$(dirname "$0")"

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

check_status=0

# ==============================================================================
# 1. Check Files Exist
# ==============================================================================

echo "1️⃣  Checking backend files..."
echo ""

files_to_check=(
  "app/Http/Controllers/CallController.php"
  "app/Events/IncomingCall.php"
  "app/Events/CallAccepted.php"
  "app/Events/CallEnded.php"
  "app/Events/IceCandidate.php"
  "app/Events/GroupCallEvent.php"
  "routes/api.php"
  "routes/channels.php"
)

for file in "${files_to_check[@]}"; do
  if [ -f "$file" ]; then
    echo -e "${GREEN}✓${NC} $file"
  else
    echo -e "${RED}✗${NC} $file (MISSING)"
    check_status=1
  fi
done

echo ""

# ==============================================================================
# 2. Check PHP Syntax
# ==============================================================================

echo "2️⃣  Checking PHP syntax..."
echo ""

php_files=(
  "app/Http/Controllers/CallController.php"
  "app/Events/IncomingCall.php"
  "app/Events/CallAccepted.php"
  "app/Events/CallEnded.php"
)

for file in "${php_files[@]}"; do
  if php -l "$file" > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} $file (syntax OK)"
  else
    echo -e "${RED}✗${NC} $file (SYNTAX ERROR)"
    check_status=1
  fi
done

echo ""

# ==============================================================================
# 3. Check Database Migrations
# ==============================================================================

echo "3️⃣  Checking database migrations..."
echo ""

migrations=(
  "database/migrations/2026_04_05_000002_create_calls_table.php"
  "database/migrations/2026_04_07_000004_add_call_type_to_calls_table.php"
  "database/migrations/2026_04_07_000005_add_group_fields_to_calls_table.php"
)

for migration in "${migrations[@]}"; do
  if [ -f "$migration" ]; then
    echo -e "${GREEN}✓${NC} Migration exists: $(basename $migration)"
  else
    echo -e "${YELLOW}⚠${NC}  Missing migration: $(basename $migration)"
  fi
done

echo ""

# ==============================================================================
# 4. Check Routes
# ==============================================================================

echo "4️⃣  Checking API routes..."
echo ""

if php artisan route:list --path=api/call 2>/dev/null | grep -q "call/start"; then
  echo -e "${GREEN}✓${NC} Call routes registered"

  # Count routes
  route_count=$(php artisan route:list --path=api/call 2>/dev/null | grep -c "api/call" || echo 0)
  echo "  Found $route_count call-related routes"
else
  echo -e "${RED}✗${NC} Call routes not found"
  check_status=1
fi

echo ""

# ==============================================================================
# 5. Check Configuration
# ==============================================================================

echo "5️⃣  Checking configuration..."
echo ""

# Check .env
if grep -q "BROADCAST_CONNECTION=pusher" .env 2>/dev/null; then
  echo -e "${GREEN}✓${NC} BROADCAST_CONNECTION set to pusher"
else
  echo -e "${YELLOW}⚠${NC}  BROADCAST_CONNECTION may not be set"
fi

if grep -q "PUSHER_PORT=6001" .env 2>/dev/null; then
  echo -e "${GREEN}✓${NC} PUSHER_PORT configured (6001)"
else
  echo -e "${YELLOW}⚠${NC}  PUSHER_PORT not configured correctly"
fi

echo ""

# ==============================================================================
# 6. Check Event Classes
# ==============================================================================

echo "6️⃣  Checking event classes..."
echo ""

events=(
  "IncomingCall:ShouldBroadcastNow"
  "CallAccepted:ShouldBroadcastNow"
  "CallEnded:ShouldBroadcastNow"
  "IceCandidate:ShouldBroadcastNow"
  "GroupCallEvent:ShouldBroadcastNow"
)

for event_check in "${events[@]}"; do
  event_name="${event_check%:*}"
  event_interface="${event_check#*:}"
  event_file="app/Events/${event_name}.php"

  if [ -f "$event_file" ]; then
    if grep -q "implements $event_interface" "$event_file"; then
      echo -e "${GREEN}✓${NC} $event_name implements $event_interface"
    else
      echo -e "${YELLOW}⚠${NC}  $event_name might not implement $event_interface"
    fi
  fi
done

echo ""

# ==============================================================================
# 7. Check CallService
# ==============================================================================

echo "7️⃣  Checking frontend CallService..."
echo ""

if [ -f "src/services/CallService.js" ]; then
  echo -e "${GREEN}✓${NC} CallService.js exists"

  # Check for key methods
  methods=("startCall" "answerCall" "sendIce" "endCall" "getCallHistory")
  for method in "${methods[@]}"; do
    if grep -q "^\s*${method}" src/services/CallService.js; then
      echo -e "  ${GREEN}✓${NC} Method: $method"
    else
      echo -e "  ${YELLOW}?${NC} Method: $method (may exist differently)"
    fi
  done
else
  echo -e "${YELLOW}⚠${NC}  CallService.js not found"
fi

echo ""

# ==============================================================================
# 8. Summary
# ==============================================================================

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                       VERIFICATION RESULT                      ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

if [ $check_status -eq 0 ]; then
  echo -e "${GREEN}✅ ALL CHECKS PASSED${NC}"
  echo ""
  echo "Next steps:"
  echo "  1. Run: php artisan migrate"
  echo "  2. Run: php artisan websockets:serve"
  echo "  3. In another terminal: php artisan serve"
  echo "  4. Visit: http://127.0.0.1:8080"
  echo "  5. Open browser console and test calls"
  echo ""
else
  echo -e "${RED}❌ SOME CHECKS FAILED${NC}"
  echo ""
  echo "Please fix the issues above before continuing."
  echo ""
fi

# ==============================================================================
# 9. Quick Diagnostic Commands
# ==============================================================================

echo "📊 Diagnostic Commands:"
echo ""
echo "  Check routes:"
echo "    php artisan route:list --path=api/call"
echo ""
echo "  Check migrations:"
echo "    php artisan migrate:status"
echo ""
echo "  Check service provider:"
echo "    grep -r 'BroadcastServiceProvider' app/Providers/"
echo ""
echo "  Test WebSocket server:"
echo "    php artisan websockets:serve"
echo ""
echo "  Monitor database:"
echo "    SELECT COUNT(*) FROM calls;"
echo ""
echo "  Check JWT config:"
echo "    grep JWT_ .env"
echo ""

echo "═════════════════════════════════════════════════════════════════"
echo ""

