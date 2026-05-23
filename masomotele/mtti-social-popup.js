// MTTI Social Popup — Graduate Story Widget v2 (with CNA)
(function() {
  if (sessionStorage.getItem('mtti_sp_shown')) return;

  const graduates = [
    {
      name: "Faith Wanjiru",
      course: "Computer Applications",
      year: "2025",
      story: "I joined MTTI with zero computer knowledge. 6 months later I got a job at a data entry firm in Nairobi earning KES 35,000/month. MTTI changed my life!",
      emoji: "💻",
      location: "Nairobi",
      highlight: null
    },
    {
      name: "Nurse Mercy Chebet",
      course: "Nursing Assistant & Caregiver",
      year: "2025",
      story: "I completed the Nursing Assistant course at MTTI in 6 months. I am now working at a private clinic in Eldoret earning KES 25,000/month. Healthcare is a career that never lacks jobs. Join CNA today!",
      emoji: "🏥",
      location: "Eldoret",
      highlight: "🔥 🔥 Nursing Assistant — Next intake open!"
    },
    {
      name: "Brian Kiprotich",
      course: "Mobile Phone Repair",
      year: "2025",
      story: "After completing my course I opened my own repair shop in Eldoret. I now earn more than KES 60,000 monthly. Best investment I ever made.",
      emoji: "📱",
      location: "Eldoret",
      highlight: null
    },
    {
      name: "Nurse Ann Achieng",
      course: "Nursing Assistant & Caregiver",
      year: "2024",
      story: "The Nursing Assistant course at MTTI gave me a TVETA-accredited certificate that got me employed within 2 months of graduating. If you want a stable healthcare career, this is it.",
      emoji: "💉",
      location: "Kisumu",
      highlight: "🔥 🔥 Nursing Assistant — Next intake open!"
    },
    {
      name: "Grace Achieng",
      course: "Digital Marketing",
      year: "2024",
      story: "MTTI gave me skills to run social media for 3 businesses. I work from home and earn on my own terms. Thank you MTTI!",
      emoji: "📈",
      location: "Kisumu",
      highlight: null
    },
    {
      name: "Samuel Mutua",
      course: "Computer Networking",
      year: "2025",
      story: "Got certified, got hired. I now work as a network technician at a hospital in Thika. MTTI's practical training made all the difference.",
      emoji: "🌐",
      location: "Thika",
      highlight: null
    }
  ];

  let current = Math.floor(Math.random() * graduates.length);

  const css = `
    #mtti-sp-overlay {
      display:none;position:fixed;inset:0;z-index:999999;
      background:rgba(0,0,0,0.72);backdrop-filter:blur(5px);
      align-items:center;justify-content:center;padding:16px;
    }
    #mtti-sp-overlay.show{display:flex;animation:mttiFadeIn 0.4s ease;}
    @keyframes mttiFadeIn{from{opacity:0}to{opacity:1}}
    @keyframes mttiFadeUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    #mtti-sp-box {
      background:#fff;border-radius:24px;max-width:430px;width:100%;
      box-shadow:0 30px 80px rgba(0,0,0,0.4);
      overflow:hidden;animation:mttiFadeUp 0.5s ease;
      font-family:'Segoe UI',sans-serif;
    }
    #mtti-sp-header {
      background:linear-gradient(135deg,#3D6318 0%,#5a8c2a 60%,#FF9700 100%);
      padding:22px 24px 18px;position:relative;
    }
    #mtti-sp-header .sp-badge {
      display:inline-flex;align-items:center;gap:6px;
      background:rgba(255,255,255,0.2);border-radius:20px;
      padding:4px 12px;font-size:11px;font-weight:700;
      color:#fff;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:10px;
    }
    #mtti-sp-header h2 {color:#fff;font-size:19px;font-weight:800;margin:0 0 3px;line-height:1.3;}
    #mtti-sp-header p {color:rgba(255,255,255,0.82);font-size:12px;margin:0;}
    #mtti-sp-close {
      position:absolute;top:12px;right:14px;background:rgba(255,255,255,0.2);
      border:none;color:#fff;width:28px;height:28px;border-radius:50%;
      font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;
    }
    #mtti-sp-story {
      padding:18px 24px 10px;min-height:150px;transition:opacity 0.3s;
    }
    .sp-highlight {
      background:linear-gradient(90deg,#fff8e1,#fff3cd);
      border:1.5px solid #FF9700;border-radius:8px;
      padding:6px 12px;font-size:12px;font-weight:700;color:#b8600a;
      margin-bottom:10px;display:inline-block;
    }
    .sp-grad-top{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
    .sp-grad-avatar{
      width:50px;height:50px;border-radius:50%;
      background:linear-gradient(135deg,#FF9700,#e67e00);
      display:flex;align-items:center;justify-content:center;
      font-size:24px;flex-shrink:0;box-shadow:0 4px 12px rgba(255,151,0,0.3);
    }
    .sp-grad-name{font-weight:800;color:#1a1a1a;font-size:15px;}
    .sp-grad-meta{font-size:11px;color:#3D6318;font-weight:600;margin-top:2px;}
    .sp-grad-quote{
      font-size:13.5px;color:#444;line-height:1.65;
      border-left:3px solid #FF9700;padding-left:12px;font-style:italic;
    }
    .sp-dots{display:flex;gap:5px;justify-content:center;padding:8px 0 4px;}
    .sp-dot{width:6px;height:6px;border-radius:50%;background:#ddd;border:none;cursor:pointer;padding:0;transition:all 0.2s;}
    .sp-dot.active{background:#3D6318;width:18px;border-radius:3px;}
    #mtti-sp-actions{padding:14px 24px 20px;border-top:1px solid #f0f0f0;}
    #mtti-sp-actions p{
      font-size:11.5px;color:#888;text-align:center;margin:0 0 10px;font-weight:700;
      text-transform:uppercase;letter-spacing:0.5px;
    }
    .sp-action-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;}
    .sp-action-btn{
      display:flex;align-items:center;justify-content:center;gap:6px;
      padding:11px 8px;border-radius:12px;font-weight:700;font-size:12.5px;
      text-decoration:none;border:none;cursor:pointer;
      transition:transform 0.15s,box-shadow 0.15s;
      box-shadow:0 2px 8px rgba(0,0,0,0.12);
    }
    .sp-action-btn:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.18);}
    .sp-fb{background:#1877f2;color:#fff;}
    .sp-tt{background:#010101;color:#fff;}
    .sp-share{background:#25d366;color:#fff;}
    .sp-web{background:#FF9700;color:#fff;}
    .sp-cna{
      grid-column:1/-1;background:linear-gradient(90deg,#3D6318,#5a8c2a);
      color:#fff;font-size:13px;padding:13px;
    }
    .sp-skip{
      display:block;text-align:center;color:#bbb;font-size:11px;
      background:none;border:none;cursor:pointer;width:100%;padding:4px;
      margin-top:4px;
    }
  `;

  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  function getGrad(i) {
    const g = graduates[i];
    const highlightHtml = g.highlight ? `<div class="sp-highlight">${g.highlight}</div>` : '';
    return `
      ${highlightHtml}
      <div class="sp-grad-top">
        <div class="sp-grad-avatar">${g.emoji}</div>
        <div>
          <div class="sp-grad-name">${g.name}</div>
          <div class="sp-grad-meta">📍 ${g.location} &nbsp;·&nbsp; ${g.course} &nbsp;·&nbsp; ${g.year}</div>
        </div>
      </div>
      <div class="sp-grad-quote">"${g.story}"</div>
    `;
  }

  function getDots(active) {
    return graduates.map((_, i) =>
      `<button class="sp-dot${i===active?' active':''}" onclick="window.__mtti_sp_goto(${i})"></button>`
    ).join('');
  }

  const html = `
    <div id="mtti-sp-overlay">
      <div id="mtti-sp-box">
        <div id="mtti-sp-header">
          <button id="mtti-sp-close" onclick="window.__mtti_sp_close()">✕</button>
          <div class="sp-badge">🎓 Student Success Stories</div>
          <h2>Real Students. Real Results.</h2>
          <p>See how MTTI graduates are thriving across Kenya</p>
        </div>
        <div id="mtti-sp-story">${getGrad(current)}</div>
        <div class="sp-dots" id="mtti-sp-dots">${getDots(current)}</div>
        <div id="mtti-sp-actions">
          <p>Follow us &amp; stay connected</p>
          <div class="sp-action-grid">
            <a href="https://web.facebook.com/profile.php?id=61582282297969" target="_blank" class="sp-action-btn sp-fb">📘 Facebook</a>
            <a href="https://www.tiktok.com/@masomoteletechnical" target="_blank" class="sp-action-btn sp-tt">🎵 TikTok</a>
            <button class="sp-action-btn sp-share" onclick="window.__mtti_sp_share()">📲 Share Portal</button>
            <a href="https://masomoteletraining.co.ke" target="_blank" class="sp-action-btn sp-web">🌐 Website</a>
            <a href="https://wa.me/254712464936?text=Hello%20MTTI%2C%20I%20am%20interested%20in%20the%20Nursing%20Assistant%20%26%20Caregiver%20course.%20Please%20send%20me%20details." target="_blank" class="sp-action-btn sp-cna">🏥 Enquire — Nursing Assistant & Caregiver · KES 59,000</a>
          </div>
          <button class="sp-skip" onclick="window.__mtti_sp_close()">Maybe later</button>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', html);

  window.__mtti_sp_close = function() {
    document.getElementById('mtti-sp-overlay').classList.remove('show');
    sessionStorage.setItem('mtti_sp_shown', '1');
  };

  window.__mtti_sp_goto = function(i) {
    current = i;
    const story = document.getElementById('mtti-sp-story');
    story.style.opacity = '0';
    setTimeout(function() {
      story.innerHTML = getGrad(current);
      document.getElementById('mtti-sp-dots').innerHTML = getDots(current);
      story.style.opacity = '1';
    }, 200);
  };

  window.__mtti_sp_share = function() {
    const url = window.location.href;
    const text = 'Check out this free learning portal by MTTI Eldoret! 🎓 ' + url;
    if (navigator.share) {
      navigator.share({ title: 'MTTI Free Learning Portal', text: text, url: url });
    } else {
      navigator.clipboard.writeText(url).then(function() {
        alert('Link copied! Share it with a friend 📲');
      });
    }
  };

  // Auto-rotate every 5 seconds
  var rotateTimer = setInterval(function() {
    window.__mtti_sp_goto((current + 1) % graduates.length);
  }, 5000);

  // Show after 25 seconds
  setTimeout(function() {
    document.getElementById('mtti-sp-overlay').classList.add('show');
    sessionStorage.setItem('mtti_sp_shown', '1');
  }, 25000);

})();
