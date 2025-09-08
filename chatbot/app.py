from flask import Flask, render_template, request, jsonify
from groq import Groq
import os

app = Flask(__name__)

client = Groq(api_key=os.getenv("YOUR_API_KEY"))  

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/chat", methods=["POST"])
def chat():
    user_message = request.json.get("message")
    system_prompt = "You are a helpful, friendly AI assistant for students. Always respond politely and concisely. Be sure to provide accurate information professionally. You can only help with topics related to academics, campus life, events, and general university information. If you don't know the answer, say 'I'm sorry, I don't have that information.' Do not make up answers."
    try:
        response = client.chat.completions.create(
            model="llama-3.1-8b-instant",
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_message}     
            ]
        )
        bot_reply = response.choices[0].message.content
        return jsonify({"reply": bot_reply})
    except Exception as e:
        return jsonify({"reply": f"Error: {str(e)}"})

if __name__ == "__main__":
    app.run(debug=True, port=8080)  
