#!/usr/bin/env python3
# coding: utf-8

import sys
import re
import tkinter as tk
from tkinter import filedialog, messagebox

def sanitize_file_name (name):
    # OSで禁止されている文字(\ / : * ? " < > |)と空白文字をアンダースコアに置換
    return re.sub(r'[\\/:*?"<>|\s]', '_', name)

def decorate_text (text):
    # 太字マーカー(**, __)をBタグに変換し、エスケープ文字を処理する。
    # バックスラッシュでエスケープされていない ** または __ をBタグに置換
    text = re.sub(r'(?<!\\)\*\*(.*?)(?<!\\)\*\*', r'<b>\1</b>', text)
    text = re.sub(r'(?<!\\)__(.*?)(?<!\\)__', r'<b>\1</b>', text)
    # エスケープされていたマーカーからバックスラッシュを除去してリテラルにする
    return text.replace(r'\*', '*').replace(r'\_', '_')

def convert_document_file (input_file_path, output_dir_path, split_by_h3=False):
    try:
        with open(input_file_path, 'r', encoding='utf-8') as f:
            lines = f.readlines()
    except FileNotFoundError:
        print(f"Error: File {input_file_path} not found.")
        return
    
    main_title = ""
    index_items = [] # (type, text, file_name) のリスト
    sections = [] # (file_name, lines) のリスト
    current_section = []
    current_file_name = ""
    current_h2 = ""
    
    split_prefix = '### ' if split_by_h3 else '## '
    
    for line in lines:
        if line.startswith('# '):
            main_title = line[2:].strip()
            continue
        
        if split_by_h3 and line.startswith('## '):
            current_h2 = line[3:].strip()
            index_items.append(('h2', current_h2, None))
            continue
        
        if line.startswith(split_prefix):
            if current_section:
                sections.append((current_file_name, current_section))
            current_section = [line]
            title = line[len(split_prefix):].strip()
            base_name = f"{current_h2}_{title}" if split_by_h3 and current_h2 else title
            current_file_name = f"{sanitize_file_name(base_name)}.html"
            index_items.append(('link', title, current_file_name))
        elif current_section:
            current_section.append(line)
    
    if current_section:
        sections.append((current_file_name, current_section))
    
    # index.html の生成
    index_html = []
    if main_title:
        index_html.append(f"<h1>{decorate_text(main_title)}</h1>")
    
    in_list = False
    for itype, text, fname in index_items:
        if itype == 'h2':
            if in_list:
                index_html.append("</ul>")
                in_list = False
            index_html.append("    ")
            index_html.append(f"<h2>{decorate_text(text)}</h2>")
        else:
            if not in_list:
                index_html.append("    ")
                index_html.append("<ul>")
                in_list = True
            display_text = re.sub(r'\s*\([^)]*\)$', '', text)
            index_html.append(f'    <li><a href="{fname}">{decorate_text(display_text)}</a></li>')
    if in_list:
        index_html.append("</ul>")
    
    with open(output_dir_path + "/index.html", 'w', encoding='utf-8') as out_f:
        out_f.write("\n".join(index_html))
    
    for output_file_name, section in sections:
        # 出力ファイル名の決定 (最初の行の内容を利用)
        header_line = section[0].strip()
        title_text = header_line[len(split_prefix):].strip()
        
        # 分割レベルに応じた内部見出しのプレフィックス判定
        h2_prefix = '#### ' if split_by_h3 else '### '
        h3_prefix = '##### ' if split_by_h3 else '#### '
        
        # H1タグの生成
        h1_tag = f"<h1>{decorate_text(title_text)}</h1>"
        
        toc_lines = []
        content_lines = []
        h2_idx, h3_idx = 1, 1
        first_header_idx = None
        
        # 各セクション内の行を処理
        skip_next_empty = False
        for line in section[1:]:
            # 末尾のスペースを除去 (改行コードも含む)
            clean_line = line.rstrip('\r\n').rstrip(' ')
            
            if clean_line.startswith(h2_prefix):
                # 前の空行を除去
                while content_lines and content_lines[-1].strip() == "<br>":
                    content_lines.pop()
                if first_header_idx is None:
                    first_header_idx = len(content_lines)
                
                # H2タグとIDの設定
                text = clean_line[len(h2_prefix):].strip()
                processed = decorate_text(text)
                tag_id = f"h2_{h2_idx}"
                h2_idx += 1
                
                toc_text = re.sub(r'\s*\([^)]*\)$', '', text)
                toc_lines.append(f'    <li><a href="#{tag_id}">{decorate_text(toc_text)}</a></li>')
                
                content_lines.append("    ")
                content_lines.append(f'<h2 id="{tag_id}">{processed}</h2>')
                
                skip_next_empty = True
            elif clean_line.startswith(h3_prefix):
                # 前の空行を除去
                while content_lines and content_lines[-1].strip() == "<br>":
                    content_lines.pop()
                if first_header_idx is None:
                    first_header_idx = len(content_lines)
                
                # H3タグとIDの設定
                text = clean_line[len(h3_prefix):].strip()
                processed = decorate_text(text)
                tag_id = f"h3_{h3_idx}"
                h3_idx += 1
                
                toc_text = re.sub(r'\s*\([^)]*\)$', '', text)
                toc_lines.append(f'    <li>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#{tag_id}">{decorate_text(toc_text)}</a></li>')
                
                content_lines.append("    ")
                content_lines.append(f'    <h3 id="{tag_id}">{processed}</h3>')
                
                skip_next_empty = True
            else:
                # 後の空行を除去
                if skip_next_empty and not clean_line:
                    continue
                skip_next_empty = False
                
                # 行頭のスペースを&nbsp;に変換
                l_stripped = clean_line.lstrip(' ')
                num_leading = len(clean_line) - len(l_stripped)
                processed = "    " + ("&nbsp;" * num_leading) + decorate_text(l_stripped)
                content_lines.append(processed + "<br>")
        
        # ページ構成: 見出し以外の導入文があれば、目次の前に配置する
        toc_html = ["    ", "<ul>"] + toc_lines + ["</ul>"] if toc_lines else []
        
        if first_header_idx is None:
            pre_toc_content = content_lines
            post_toc_content = []
        else:
            pre_toc_content = content_lines[:first_header_idx]
            post_toc_content = content_lines[first_header_idx:]
        
        # H1タグ直後の空行を除去
        while pre_toc_content and pre_toc_content[0].strip() == "<br>":
            pre_toc_content.pop(0)
        
        # 目次がある場合、その直後の空行を除去
        if toc_html:
            while post_toc_content and post_toc_content[0].strip() == "<br>":
                post_toc_content.pop(0)
        
        # ファイル末尾の空行を除去
        while post_toc_content and post_toc_content[-1].strip() == "<br>":
            post_toc_content.pop()
        while not post_toc_content and pre_toc_content and pre_toc_content[-1].strip() == "<br>":
            pre_toc_content.pop()
        
        full_html = [h1_tag] + pre_toc_content + toc_html + post_toc_content
        
        with open(output_dir_path + "/" + output_file_name, 'w', encoding='utf-8') as out_f:
            out_f.write("\n".join(full_html))

def start_gui ():
    root = tk.Tk()
    root.title("ドキュメントファイル変換ツール")
    root.geometry("480x240")
    root.configure(bg="white")
    root.resizable(False, False)
    
    # スタイル設定
    BTN_BG = "#88ccee"
    ENTRY_BG = "#f7f7f7"
    
    # 入力ファイル欄
    input_frame = tk.Frame(root, bg="white", width=480, height=60)
    input_frame.place(x=0, y=5)
    
    tk.Label(input_frame, text="変換するファイル:", bg="white").place(x=10, y=0)
    
    input_entry = tk.Entry(input_frame, relief="flat", bg=ENTRY_BG)
    input_entry.place(x=10, y=30, width=375, height=30)
    
    def select_file ():
        path = filedialog.askopenfilename(filetypes=[("Markdown", "*.md"), ("All", "*.*")])
        if path:
            input_entry.delete(0, tk.END)
            input_entry.insert(0, path)
    
    tk.Button(input_frame, text="選択", relief="flat", bg=BTN_BG, 
              command=select_file).place(x=390, y=30, width=80, height=30)
    
    # 出力フォルダ欄
    output_frame = tk.Frame(root, bg="white", width=480, height=60)
    output_frame.place(x=0, y=70)
    
    tk.Label(output_frame, text="出力先フォルダ:", bg="white").place(x=10, y=0)
    
    output_entry = tk.Entry(output_frame, relief="flat", bg=ENTRY_BG)
    output_entry.place(x=10, y=30, width=375, height=30)
    
    def select_folder ():
        path = filedialog.askdirectory()
        if path:
            output_entry.delete(0, tk.END)
            output_entry.insert(0, path)
    
    tk.Button(output_frame, text="選択", relief="flat", bg=BTN_BG, 
              command=select_folder).place(x=390, y=30, width=80, height=30)
    
    # オプション設定
    option_frame = tk.Frame(root, bg="white", width=480, height=40)
    option_frame.place(x=0, y=140)
    
    split_var = tk.BooleanVar(value=False)
    tk.Checkbutton(option_frame, text="見出し3で出力ファイルを分割する", variable=split_var, bg="white", activebackground="white").place(x=10, y=5)
    
    # 実行ボタン
    def execute ():
        in_path = input_entry.get()
        out_dir = output_entry.get()
        is_split_h3 = split_var.get()
        if not in_path or not out_dir:
            messagebox.showwarning("入力エラー", "変換対象ファイルと出力先フォルダを選択してください。")
            return
        try:
            convert_document_file(in_path, out_dir, is_split_h3)
            messagebox.showinfo("完了", "HTMLファイルの出力が完了しました。")
        except Exception as e:
            messagebox.showerror("エラー", f"処理中にエラーが発生しました:\n{e}")
    
    run_btn = tk.Button(root, text="実行", relief="flat", bg=BTN_BG, command=execute)
    run_btn.place(x=160, y=190, width=160, height=30)
    
    root.mainloop()

if __name__ == "__main__":
    # 引数があればコマンドライン実行、なければGUI起動
    if len(sys.argv) >= 4:
        convert_document_file(sys.argv[1], sys.argv[2], sys.argv[3].lower() in {'true', 't', 'yes', 'y'})
    elif len(sys.argv) == 3:
        convert_document_file(sys.argv[1], sys.argv[2])
    else:
        start_gui()
