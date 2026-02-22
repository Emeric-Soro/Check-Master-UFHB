import json
import os

session_file = "C:/Users/ahopa/.local/share/opencode/storage/sessions/ses_37a7b6c88ffe5WG7ycDNOG9G4Y.json"

try:
    with open(session_file, 'r', encoding='utf-8') as f:
        data = json.load(f)

    # Sometimes the top-level is a dict with 'messages'
    if isinstance(data, dict) and "messages" in data:
        messages = data["messages"]
    else:
        messages = data

    for msg in messages:
        if msg.get("role") == "assistant" and msg.get("tool_calls"):
            for tc in msg["tool_calls"]:
                if tc.get("function", {}).get("name") == "write":
                    args = json.loads(tc["function"]["arguments"])
                    file_path = args.get("filePath")
                    content = args.get("content")
                    if file_path and content:
                        print(f"Recovering {file_path}")
                        os.makedirs(os.path.dirname(file_path), exist_ok=True)
                        with open(file_path, 'w', encoding='utf-8') as out:
                            out.write(content)
except Exception as e:
    print(f"Error: {e}")
