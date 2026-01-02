# Local installation with Docker

## Deploying the container

Download Docker Desktop and open a terminal:

<img width="1553" height="801" alt="image" src="/imgstore/531410774-2576af5b-cb0f-42ab-885f-d61255ca528e.png" />

Go to the local Git repository of the project and run the command `docker compose up --build`:

<img width="711" height="210" alt="image" src="/imgstore/531411110-3a85a86e-013b-4fe6-acd3-5d275626e07f.png" />

(:warning: You can first copy the `.env.example` file to `.env` and change the MySQL root password there. If you don't do this, the root password will be `123`.)

Well, that's basically it. Docker will deploy all the necessary containers and launch a local web server:

<img width="1553" height="801" alt="image" src="/imgstore/531412314-b0b8752b-7b08-4176-a633-455eba6d0b65.png" />

## Setting up the Lobby and installing the Master database

Open `localhost` and enter credentials to connect to MySQL root:

<img width="931" height="525" alt="image" src="/imgstore/531413062-eea522b1-7e2c-4c96-b14e-f18ef7c31904.png" />

Click the "Install" button and make sure the green text appears. You're all set.

## Installation of the Universe

Open local phpMyAdmin at `localhost:8080` and log in as root:

<img width="406" height="517" alt="image" src="/imgstore/531413384-edb51356-290c-410d-97e3-3ea8011f8b20.png" />

Create a `uni` database to store universe data:

<img width="834" height="302" alt="image" src="/imgstore/531413655-2de38ce3-8487-4bd4-8ac2-2ff5002343da.png" />

Go to the game at `localhost/game` and configure everything as shown in the picture:

<img width="987" height="885" alt="image" src="/imgstore/531414041-a9b2ecc3-c238-4a2d-b018-fad229d88dc1.png" />

After clicking the "Install" button, make sure a green sign appears. The universe is ready to play.

## Log in as Legor

Enter Legor's login and password on the main page and you'll be taken to the game:

<img width="1720" height="700" alt="image" src="/imgstore/531416154-21a6b1a9-38bc-4c46-8811-1fc6b34b0685.png" />

Now you can test the battle engine. To do this, log into the admin panel and launch the Battlesim with a standard battle of 200 cruisers versus 1667 light fighters:

<img width="597" height="698" alt="image" src="/imgstore/531416637-5537d55c-bfc5-49e7-9d36-eb621cd33dcf.png" />

If you see a green log, then everything is OK.