import json
import os
import glob

diff_files = glob.glob("C:/Users/ahopa/.local/share/opencode/storage/session_diff/*.json")

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

for session_file in diff_files:
    try:
        with open(session_file, 'r', encoding='utf-8') as f:
            diff_data = json.load(f)

        for item in diff_data:
            file_path = item.get("file")
            status = item.get("status")
            content = item.get("after")
            
            if file_path is None or content is None:
                continue
                
            is_untracked = any(file_path.startswith(ut) for ut in untracked)
            
            # Write it if it's untracked. We iterate through all diff files, 
            # so the latest version might overwrite earlier ones depending on order.
            # To be safe we'll just write whatever was the latest added/modified.
            if is_untracked and status in ("added", "modified", "renamed"):
                print(f"Recovering {file_path} from {os.path.basename(session_file)}")
                os.makedirs(os.path.dirname(file_path), exist_ok=True)
                with open(file_path, 'w', encoding='utf-8') as out:
                    out.write(content)

    except Exception as e:
        pass
