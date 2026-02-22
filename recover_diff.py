import json
import os

session_file = "C:/Users/ahopa/.local/share/opencode/storage/session_diff/ses_37a7b6c88ffe5WG7ycDNOG9G4Y.json"

try:
    with open(session_file, 'r', encoding='utf-8') as f:
        diff_data = json.load(f)

    for item in diff_data:
        file_path = item.get("file")
        status = item.get("status")
        content = item.get("after")
        
        # Don't overwrite tracked files that I already applied the patch to, 
        # unless they are the untracked ones. Or actually, just write the untracked ones.
        untracked = [
            "app/utils/ComponentHelper.php",
            "app/utils/FormHelper.php",
            "app/utils/PaginationHelper.php",
            "app/utils/TableHelper.php",
            "docs2/",
            "public/assets/",
            "ressources/components/",
            "ressources/views/v2/"
        ]
        
        is_untracked = any(file_path.startswith(ut) for ut in untracked)
        
        if is_untracked and status in ("added", "modified") and content is not None:
            print(f"Recovering {file_path}")
            os.makedirs(os.path.dirname(file_path), exist_ok=True)
            with open(file_path, 'w', encoding='utf-8') as out:
                out.write(content)

except Exception as e:
    print(f"Error: {e}")
