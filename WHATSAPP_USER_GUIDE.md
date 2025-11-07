# WhatsApp Reconnection User Guide

## What This Feature Does

When your WhatsApp session gets disconnected, the system will automatically detect it and show you a QR code to reconnect. No more confusing error messages!

## How It Works

### Automatic Detection

The system detects disconnections when:
- ✉️ You try to send a message
- 📤 You try to send bulk messages  
- 🔔 You try to send notifications

When disconnected, a popup will appear with a QR code and instructions.

### Manual Check

You can also manually check your WhatsApp connection status:

1. Go to **WhatsApp Management** page
2. Select your **Company** from the dropdown
3. Look at the **WhatsApp Session Status** card at the top
4. If disconnected, click the **"Show QR Code"** button

## Reconnecting Your WhatsApp

### Step-by-Step Instructions

When you see the QR code popup:

1. **Open WhatsApp** on your phone
2. Tap **Menu** (⋮) or **Settings** (⚙️)
3. Tap **Linked Devices**
4. Tap **Link a Device**
5. **Point your phone** at the QR code on your screen
6. Wait a few seconds...
7. ✅ **Done!** The popup will close automatically

### What You'll See

#### Connected Status
```
✅ WhatsApp session is connected and ready to send messages.
```
- Green status indicator
- Everything working normally

#### Disconnected Status
```
⚠️ Session disconnected. Please scan the QR code to reconnect.
[Show QR Code Button]
```
- Red/Orange status indicator
- Click button to see QR code

## The QR Code Popup

### Features

- 📱 **Large QR Code** - Easy to scan
- 📋 **Step-by-step instructions** - No confusion
- 🔄 **Auto-refresh** - Checks connection every 5 seconds
- ✨ **Auto-close** - Closes when you're connected
- 🔃 **Manual refresh** - Force check anytime

### Popup Layout

```
┌─────────────────────────────────────┐
│  WhatsApp Session Disconnected      │
│                                  [X] │
├─────────────────────────────────────┤
│                                     │
│  Follow these steps:                │
│  1. Open WhatsApp on your phone     │
│  2. Tap Menu or Settings            │
│  3. Tap Linked Devices              │
│  4. Tap Link a Device               │
│  5. Scan this QR code               │
│                                     │
│  ┌─────────────────────────┐       │
│  │                         │       │
│  │    [QR CODE IMAGE]      │       │
│  │                         │       │
│  └─────────────────────────┘       │
│                                     │
│  🔄 Auto-refreshing every 5 sec...  │
│                                     │
│  This dialog will close             │
│  automatically once connected       │
│                                     │
├─────────────────────────────────────┤
│           [Refresh] [Close]         │
└─────────────────────────────────────┘
```

## Common Scenarios

### Scenario 1: Send Message Fails

**What happens:**
1. You click "Send Message" on a parcel
2. System detects WhatsApp is disconnected
3. QR code popup appears automatically
4. You scan the code
5. Popup closes, message is ready to send

**Next steps:**
- Just click "Send Message" again!

### Scenario 2: Bulk Messages Blocked

**What happens:**
1. You select multiple parcels
2. Click "Send Bulk Messages"
3. System detects disconnection
4. QR code popup appears
5. You reconnect by scanning

**Next steps:**
- Select your parcels again
- Send bulk messages as normal

### Scenario 3: Session Status Check

**What happens:**
1. You open WhatsApp Management
2. System automatically checks status
3. Red warning shows if disconnected
4. Click "Show QR Code" button
5. Follow instructions to reconnect

## Tips & Best Practices

### 🔍 Proactive Checking
- Check status **before** sending important messages
- Look at the status card at the top of the page
- Green = Good to go!
- Red = Need to reconnect

### 📱 Keep Phone Handy
- Have your phone ready when managing WhatsApp
- Faster reconnection if session drops

### 🔄 Auto-Refresh Benefits
- Don't spam the refresh button
- System checks every 5 seconds automatically
- Just scan and wait!

### ⚡ Quick Reconnect
- Bookmark the WhatsApp Management page
- Quick access when needed

## Troubleshooting

### QR Code Not Showing

**Problem:** QR code popup is blank

**Solutions:**
1. Click the **Refresh** button
2. Check your internet connection
3. Try selecting the company again
4. Contact support if problem persists

### Can't Scan QR Code

**Problem:** Phone won't scan the QR code

**Solutions:**
1. Make sure QR code is fully visible
2. Adjust your phone's distance from screen
3. Check phone camera is working
4. Try in better lighting
5. Click **Refresh** for a new code

### Popup Won't Close

**Problem:** Successfully scanned but popup stays open

**Solutions:**
1. Wait 5 seconds for auto-check
2. Click **Refresh** manually
3. Check status changed to "CONNECTED"
4. Click **Close** and check status card
5. Refresh the page if needed

### Session Keeps Disconnecting

**Problem:** Connection drops frequently

**Possible causes:**
1. Phone WhatsApp is closed
2. Phone is offline
3. Phone battery optimization closing WhatsApp
4. Multiple devices linked to same number

**Solutions:**
1. Keep WhatsApp open on phone
2. Disable battery optimization for WhatsApp
3. Check your internet connection
4. Unlink unused devices in WhatsApp settings

## Status Indicators Explained

| Color | Status | Meaning |
|-------|--------|---------|
| 🟢 Green | CONNECTED | Ready to send messages |
| 🟢 Green | WORKING | Session is active |
| 🔴 Red | DISCONNECTED | Need to reconnect |
| 🟡 Yellow | UNKNOWN | Checking status... |
| ⚪ Grey | NOT_CONFIGURED | API key not set |

## Notifications

### Success Messages
- ✅ "Message sent successfully!"
- ✅ "WhatsApp session connected successfully!"
- ✅ "Bulk messages sent! Success: X, Failed: Y"

### Warning Messages
- ⚠️ "WhatsApp session is disconnected. Please scan the QR code to reconnect."

### Error Messages
- ❌ "Failed to send message"
- ❌ "Unable to retrieve QR code. Please try refreshing."

## FAQ

**Q: How often does the system check my connection?**
A: Every 5 seconds when the QR code popup is open, otherwise on-demand.

**Q: Will the popup appear every time?**
A: Only when WhatsApp is actually disconnected.

**Q: Can I close the popup and reconnect later?**
A: Yes! Click the X or Close button. You can reopen it from the status card.

**Q: What if I'm connected but it shows disconnected?**
A: Click the Refresh button (🔄) at the top of the status card.

**Q: Does this work on mobile devices?**
A: Yes! The QR code and instructions work on all devices.

**Q: Can I reconnect without the popup?**
A: The popup makes it easier, but you can also scan directly in WhatsApp's Linked Devices.

## Need Help?

If you're still having trouble:

1. 📧 Contact your system administrator
2. 📞 Call support
3. 💬 Use the help chat
4. 📖 Check the full technical documentation

## Quick Reference

```
🟢 Connected → Send messages freely
🔴 Disconnected → Click "Show QR Code" → Scan → Wait 5 sec → Done!
```

---

**Remember:** The system is designed to make reconnection as easy as possible. Just follow the on-screen instructions and you'll be back online in seconds! 🚀