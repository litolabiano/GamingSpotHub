<!-- ══ CONTACT ════════════════════════════════════════════════════════════ -->
<section id="contact" style="background:linear-gradient(180deg,#0d1b2a 0%,#060d1a 100%);padding:90px 0 100px;position:relative;overflow:hidden;">

    <!-- BG glow -->
    <div style="position:absolute;bottom:-80px;left:10%;width:500px;height:400px;border-radius:50%;background:radial-gradient(ellipse,rgba(95,133,218,.07),transparent 70%);pointer-events:none;"></div>

    <div class="container" style="position:relative;z-index:2;">

        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Find Us</span>
            <h2 class="section-title">Visit Good Spot</h2>
            <p class="section-subtitle">Drop in, call ahead, or send us a message — we're always game</p>
        </div>

        <div class="row g-5 align-items-start">

            <!-- Left: info -->
            <div class="col-lg-5" data-aos="fade-right">

                <!-- Contact cards -->
                <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:28px;">

                    <div class="gsh-contact-item">
                        <div class="gsh-ci-icon" style="background:linear-gradient(135deg,rgba(251,86,107,.2),rgba(251,86,107,.05));">
                            <i class="fas fa-map-marker-alt" style="color:#fb566b;"></i>
                        </div>
                        <div>
                            <div class="gsh-ci-label">Location</div>
                            <div class="gsh-ci-val">29 Don Placido Avenue, Zone 2<br>Dasmariñas, Cavite 4114</div>
                        </div>
                    </div>

                    <div class="gsh-contact-item">
                        <div class="gsh-ci-icon" style="background:linear-gradient(135deg,rgba(32,200,161,.2),rgba(32,200,161,.05));">
                            <i class="fas fa-phone" style="color:#20c8a1;"></i>
                        </div>
                        <div>
                            <div class="gsh-ci-label">Phone</div>
                            <div class="gsh-ci-val">(0917) 300 4751</div>
                        </div>
                    </div>

                    <div class="gsh-contact-item">
                        <div class="gsh-ci-icon" style="background:linear-gradient(135deg,rgba(95,133,218,.2),rgba(95,133,218,.05));">
                            <i class="fas fa-envelope" style="color:#5f85da;"></i>
                        </div>
                        <div>
                            <div class="gsh-ci-label">Email</div>
                            <div class="gsh-ci-val">gspotgaminghub@gmail.com</div>
                        </div>
                    </div>

                    <div class="gsh-contact-item">
                        <div class="gsh-ci-icon" style="background:linear-gradient(135deg,rgba(241,168,60,.2),rgba(241,168,60,.05));">
                            <i class="fas fa-clock" style="color:#f1a83c;"></i>
                        </div>
                        <div>
                            <div class="gsh-ci-label">Operating Hours</div>
                            <div class="gsh-ci-val">
                                <span style="color:#20c8a1;font-weight:700;">12:00 PM</span>
                                <span style="color:rgba(255,255,255,.4);margin:0 6px;">–</span>
                                <span style="color:#20c8a1;font-weight:700;">12:00 AM</span>
                                <span style="display:block;font-size:12px;color:rgba(255,255,255,.35);margin-top:2px;">Every day</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Social -->
                <div>
                    <div style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:12px;">Connect With Us</div>
                    <a href="https://www.facebook.com/gspotgaminghub" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:10px;background:rgba(24,119,242,.1);border:1px solid rgba(24,119,242,.3);color:#6fa8f7;font-weight:700;font-size:14px;padding:11px 20px;border-radius:12px;text-decoration:none;transition:all .25s;">
                        <i class="fab fa-facebook-f" style="font-size:1.1rem;"></i>
                        @gspotgaminghub
                    </a>
                </div>
            </div>

            <!-- Right: contact form -->
            <div class="col-lg-7" data-aos="fade-left" data-aos-delay="100">
                <div class="gsh-contact-form-wrap">
                    <div style="margin-bottom:22px;">
                        <div style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:4px;">Send Us a Message</div>
                        <div style="font-size:13px;color:rgba(255,255,255,.4);">We'll get back to you as soon as possible.</div>
                    </div>

                    <form id="contactForm">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="gsh-form-label">Your Name</label>
                                <input type="text" id="contactName" class="gsh-form-input" placeholder="Juan dela Cruz" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="gsh-form-label">Email Address</label>
                                <input type="email" id="contactEmail" class="gsh-form-input" placeholder="juan@email.com" required>
                            </div>
                            <div class="col-12">
                                <label class="gsh-form-label">Phone <span style="color:rgba(255,255,255,.3);font-weight:500;">(optional)</span></label>
                                <input type="tel" id="contactPhone" class="gsh-form-input" placeholder="09XX XXX XXXX">
                            </div>
                            <div class="col-12">
                                <label class="gsh-form-label">Message</label>
                                <textarea id="contactMessage" class="gsh-form-input" rows="5" placeholder="Hi! I'd like to ask about..." required style="resize:vertical;min-height:120px;"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" id="contactSubmitBtn" class="gsh-contact-submit">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Send Message</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="contactFeedback" style="display:none;margin-top:14px;"></div>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
.gsh-contact-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: rgba(255,255,255,.025);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 14px;
    padding: 16px 18px;
    transition: background .3s, border-color .3s;
}
.gsh-contact-item:hover {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.12);
}
.gsh-ci-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,.05);
}
.gsh-ci-label {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(255,255,255,.3);
    margin-bottom: 3px;
}
.gsh-ci-val {
    font-size: 14px;
    color: rgba(255,255,255,.8);
    font-weight: 600;
    line-height: 1.5;
}
.gsh-contact-form-wrap {
    background: rgba(10,18,40,.75);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 32px;
    backdrop-filter: blur(10px);
}
.gsh-form-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .5px;
    color: rgba(255,255,255,.5);
    margin-bottom: 7px;
}
.gsh-form-input {
    width: 100%;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 11px;
    color: #f0f0f0;
    padding: 12px 14px;
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
}
.gsh-form-input:focus {
    border-color: #20c8a1;
    box-shadow: 0 0 0 3px rgba(32,200,161,.12);
}
.gsh-form-input::placeholder { color: rgba(255,255,255,.2); }
.gsh-contact-submit {
    width: 100%;
    background: linear-gradient(135deg, #20c8a1, #17a887);
    border: none;
    border-radius: 12px;
    color: #06110a;
    font-weight: 800;
    font-size: 15px;
    padding: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all .25s;
    box-shadow: 0 4px 20px rgba(32,200,161,.25);
}
.gsh-contact-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 32px rgba(32,200,161,.4);
}
</style>

<script>
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('contactSubmitBtn');
    const fb  = document.getElementById('contactFeedback');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Sending…</span>';
    // Simulated send (no backend endpoint yet)
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Send Message</span>';
        fb.style.display = 'block';
        fb.innerHTML = '<div style="background:rgba(32,200,161,.1);border:1px solid rgba(32,200,161,.3);border-radius:12px;padding:14px 18px;color:#20c8a1;font-size:14px;font-weight:700;">✓ Message sent! We\'ll get back to you soon.</div>';
        document.getElementById('contactForm').reset();
    }, 1200);
});
</script>
