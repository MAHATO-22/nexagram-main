import urllib.request
import urllib.parse
import json
import re
import time

def translate(text, target_lang):
    try:
        # Extract base lang from lang code (e.g. zh-CN -> zh-CN, but some might need adjustment)
        # Google Translate uses zh-CN, zh-TW, etc.
        tl = target_lang
        if tl == 'zh-HK': tl = 'zh-TW'
        if tl == 'prs': tl = 'fa'
        if tl == 'mww': return text # Hmong daw might not be supported, return original
        
        url = f"https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl={tl}&dt=t&q={urllib.parse.quote(text)}"
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req) as response:
            result = json.loads(response.read().decode('utf-8'))
            return "".join([t[0] for t in result[0]])
    except Exception as e:
        print(f"Error translating to {target_lang}: {e}")
        return text

with open('languages/lang.docs', 'r', encoding='utf-8') as f:
    lang_docs = f.read().splitlines()

languages = []
for line in lang_docs:
    if ':' in line:
        code = line.split(':')[0].strip()
        if code not in ['en-US', 'en-GB', 'ja-JP', 'en', 'ja']:
            languages.append(code)

en_dict = {
    'nav_home': 'Home',
    'nav_friends': 'Connections',
    'nav_users': 'Users',
    'nav_messages': 'Messages',
    'nav_create': 'Create',
    'nav_profile': 'Profile',
    'nav_logout': 'Logout',
    'nav_settings': 'Settings',
    'language': 'Language',
    'language_settings': 'Language settings',
    'language_description': 'Choose the language used throughout the app. Languages without a translation currently use English.',
    'more': 'More',
    'suggestions_title': 'People you may know',
    'suggestions_subtitle': 'Connect with people from your class, department, and school.',
    'suggestions_launcher': 'Suggested people',
    'suggestions_close': 'Close suggestions',
    'suggestions_loading': 'Loading suggestions...',
    'suggestions_error': 'Could not load suggestions. Please try again.',
    'suggestion_same_class': 'Same class',
    'suggestion_same_id_group': 'Similar student ID',
    'suggestion_same_department': 'Same department',
    'suggestion_same_school': 'Same school',
    'suggestion_platform': 'On Nexagram',
    'suggestions_empty': 'No new people to suggest right now.',
    'suggestion_follow_error': 'Could not update follow status. Please try again.',
    'suggestion_friend_error': 'Could not send friend request. Please try again.',
    'see_all_users': 'See all users',
    'class_placeholder': 'Class (optional)',
    'school_placeholder': 'School name (optional)',
    'visibility_target_audience': 'Visibility / Target Audience',
    'visibility_public': 'Public (Everyone)',
    'visibility_followers': 'Followers Only',
    'visibility_friends': 'Friends Only',
    'visibility_only_me': 'Only Me',
    'visibility_settings_title': 'Post visibility',
    'visibility_settings_description': 'Choose the default audience for new posts. This setting is applied when you create a post.',
    'visibility_saved': 'Default audience saved.',
    'visibility_save_error': 'Choose a valid audience setting.',
    'dark_mode': 'Dark Mode',
    'light_mode': 'Light Mode',
    'like_button': 'Like',
    'comment_button': 'Comment',
    'likes_label': 'likes',
    'comments_label': 'comments',
    'comments_title': 'Comments',
    'reply_to': 'Replying to',
    'btn_reply': 'Reply',
    'no_comments': 'No comments yet.',
    'add_comment_placeholder': 'Add a comment...',
    'post_comment_btn': 'Post',
    'reg_subtitle': 'Connect with campus peers and share the latest updates.',
    'role_student': 'Student',
    'role_teacher': 'Teacher',
    'ph_student_id': 'Student ID',
    'ph_teacher_id': 'Teacher ID',
    'ph_username': 'Username',
    'ph_email': 'School Email',
    'ph_password': 'Password',
    'select_dept': 'Select Department',
    'btn_signup': 'Sign Up',
    'has_account': 'Already have an account?',
    'login_link': 'Log In',
    'search_placeholder': 'Search by username or department...',
    'tab_all': 'All',
    'tab_students': 'Students',
    'tab_teachers': 'Teachers',
    'btn_follow': 'Follow',
    'btn_following': 'Following',
    'btn_add_friend': 'Add Friend',
    'btn_pending': 'Pending',
    'btn_friends': 'Friends',
    'no_users': 'No users found.',
    'network_title': 'People & Network',
    'search_connections_placeholder': 'Search connections by name...',
    'tab_friends': 'Friends',
    'tab_requests': 'Requests',
    'tab_following': 'Following',
    'tab_followers': 'Followers',
    'follows_you': 'Follows You',
    'btn_unfriend': 'Unfriend',
    'btn_follow_back': 'Follow Back',
    'btn_accept': 'Accept',
    'btn_reject': 'Delete',
    'confirm_unfriend': 'Are you sure you want to unfriend this user?',
    'edit_profile': 'Edit Profile',
    'stat_posts': 'Posts',
    'stat_followers': 'Followers',
    'stat_following': 'Following',
    'stat_friends': 'Friends',
    'edit_caption_title': 'Edit Caption',
    'write_caption_placeholder': 'Write a caption...',
    'btn_cancel': 'Cancel',
    'btn_save': 'Save',
    'confirm_delete_post': 'Are you sure you want to delete this post?',
    'delete_post_tooltip': 'Delete Post',
    'edit_caption_tooltip': 'Edit Caption',
    'create_post_title': 'Create New Post',
    'dropzone_title': 'Drag & drop photos or videos',
    'dropzone_subtitle': 'Multiple photos allowed (PNG, JPG, MP4)',
    'add_more_photos': 'Add More',
    'caption_label': 'Caption',
    'btn_share': 'Share',
    'err_invalid_format': 'Unsupported file format included. (PNG, JPG, MP4, etc.)',
    'err_empty_post': 'Please enter a caption or upload image/video.'
}

# limit languages for demo, or do all if requested. The user requested *all*.
# doing 100 languages * 1 request (if I combine) or 100 requests. 
# let's just create a massive dictionary by doing 1 request per language, translating a single JSON string.
all_translations = ""
print(f"Translating for {len(languages)} languages...")
for lang in languages:
    # chunk text to translate everything at once for the language
    text_to_translate = json.dumps(en_dict)
    translated_json_str = translate(text_to_translate, lang.split('-')[0]) # Use primary tag
    try:
        translated_dict = json.loads(translated_json_str)
        # format as PHP array
        php_array = f"    '{lang}' => [\n"
        for k, v in translated_dict.items():
            safe_v = str(v).replace("'", "\\'")
            php_array += f"        '{k}' => '{safe_v}',\n"
        php_array += "    ],\n"
        all_translations += php_array
        print(f"Translated {lang}")
    except:
        # Fallback to English if parsing fails
        php_array = f"    '{lang}' => [\n"
        for k, v in en_dict.items():
            safe_v = str(v).replace("'", "\\'")
            php_array += f"        '{k}' => '{safe_v}',\n"
        php_array += "    ],\n"
        all_translations += php_array
        print(f"Failed {lang}, used English fallback")
    time.sleep(0.5)

# Now inject into php/lang.php
with open('php/lang.php', 'r', encoding='utf-8') as f:
    content = f.read()

# find where en ends and insert all_translations
# 'err_empty_post' => 'Please enter a caption or upload image/video.'\n    ]

insert_point = content.find("    'en' => [")
end_of_en = content.find("    ]\n];", insert_point)
if end_of_en != -1:
    new_content = content[:end_of_en + 5] + ",\n" + all_translations + content[end_of_en + 5:]
    with open('php/lang.php', 'w', encoding='utf-8') as f:
        f.write(new_content)
    print("lang.php updated successfully!")
else:
    print("Could not find insertion point in lang.php")

