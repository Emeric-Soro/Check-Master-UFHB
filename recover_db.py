import sqlite3
import re
import os

db_path = "C:/Users/ahopa/.local/share/opencode/opencode.db"

try:
    conn = sqlite3.connect(db_path)
    c = conn.cursor()
    
    # Let's search all messages
    c.execute("SELECT content FROM message WHERE content LIKE '%ComponentHelper.php%'")
    for row in c.fetchall():
        content = row[0]
        # Wait, if the assistant generated it in a code block or tool call...
        # We can just write the entire message to a file so I can inspect it.
        with open("db_recovered.txt", "a", encoding="utf-8") as f:
            f.write(content + "\n\n===MESSAGE BOUNDARY===\n\n")

    conn.close()
    print("Exported messages with ComponentHelper.php to db_recovered.txt")

except Exception as e:
    print(f"Error: {e}")
