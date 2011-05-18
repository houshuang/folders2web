require 'mail'
require 'yaml'

# helper function for emailing stuff to Kindle, etc
def mail_file(file)
  # get passwords and email
  conf = YAML::load(File.read("config.yaml"))

  # we're using GMail
  options = { :address              => "smtp.gmail.com",
    :port                 => 587,
    :domain               => 'localhost',
    :user_name            => conf["email"],
    :password             => conf["password"],
    :authentication       => 'plain',
    :enable_starttls_auto => true  
  }

  Mail.defaults do
    delivery_method :smtp, options
  end
  @mail = Mail.deliver do
    to      conf["receiver"]
    from    conf["email"]
    subject 'convert'

    text_part do
      body 'sent from bibdesk'
    end


    add_file file
  end
end